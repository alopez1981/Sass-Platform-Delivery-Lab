<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Request as OperationalRequest;
use App\Models\RequestStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;

/**
 * Administrator-only operational view. Request counts and the average
 * resolution time come from OperationalRequest, which is tenant-scoped
 * automatically (see ADR 0003) — an admin only ever sees their own
 * organization's numbers here, no extra filtering needed.
 *
 * "Recent errors" and "pending queue jobs" are the two exceptions: they are
 * infrastructure-level signals shared by the whole app (failed_jobs and the
 * RabbitMQ queue have no concept of organization), not per-tenant data. To
 * keep that from ever leaking tenant business data, only the exception
 * class/message and timing are shown — never the failed job's payload,
 * which is where a request's title/description would actually live.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdministrator(), 403, 'Only an Administrator can view the dashboard.');

        return response()->json([
            'requests_by_status' => $this->requestsByStatus(),
            'avg_resolution_hours' => $this->averageResolutionHours(),
            'recent_errors' => $this->recentErrors(),
            'pending_queue_jobs' => $this->pendingQueueJobs(),
        ]);
    }

    private function requestsByStatus(): array
    {
        $counts = OperationalRequest::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $byStatus = collect(RequestStatus::cases())
            ->mapWithKeys(fn (RequestStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)]);

        return [
            ...$byStatus->all(),
            'total' => $byStatus->sum(),
        ];
    }

    /**
     * Computed in PHP rather than SQL (e.g. MySQL's TIMESTAMPDIFF) so it
     * behaves identically against MySQL (Docker) and SQLite (test suite).
     */
    private function averageResolutionHours(): ?float
    {
        $resolutions = RequestStatusHistory::query()
            ->where('to_status', RequestStatus::Resolved)
            ->with('request:id,created_at')
            ->get();

        if ($resolutions->isEmpty()) {
            return null;
        }

        $avgSeconds = $resolutions
            ->map(fn (RequestStatusHistory $history) => $history->created_at->diffInSeconds($history->request->created_at, absolute: true))
            ->average();

        return round($avgSeconds / 3600, 1);
    }

    private function recentErrors(): array
    {
        return DB::table('failed_jobs')
            ->select(['exception', 'failed_at'])
            ->latest('failed_at')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'exception' => Str::of($row->exception)->before("\n")->limit(200)->value(),
                'failed_at' => $row->failed_at,
            ])
            ->all();
    }

    private function pendingQueueJobs(): ?int
    {
        try {
            return Queue::connection('rabbitmq')->size('default');
        } catch (Throwable) {
            return null;
        }
    }
}
