<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyRequestCreated;
use App\Models\Request as OperationalRequest;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    public function index(HttpRequest $request)
    {
        $requests = OperationalRequest::query()
            ->with(['creator:id,name', 'assignee:id,name'])
            ->latest()
            ->paginate(20);

        return response()->json($requests);
    }

    public function store(HttpRequest $request)
    {
        $this->authorize('create', OperationalRequest::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $operationalRequest = OperationalRequest::create([
            ...$data,
            'created_by' => $request->user()->id,
            'status' => RequestStatus::Draft,
        ]);

        NotifyRequestCreated::dispatch($operationalRequest);

        return response()->json(
            $operationalRequest->load(['creator:id,name', 'assignee:id,name']),
            201
        );
    }

    public function show(OperationalRequest $operationalRequest)
    {
        $this->authorize('view', $operationalRequest);

        return response()->json(
            $operationalRequest->load([
                'creator:id,name',
                'assignee:id,name',
                'comments.user:id,name',
                'statusHistories.changedBy:id,name',
            ])
        );
    }

    public function updateStatus(HttpRequest $request, OperationalRequest $operationalRequest)
    {
        $this->authorize('changeStatus', $operationalRequest);

        $data = $request->validate([
            'status' => ['required', Rule::enum(RequestStatus::class)],
        ]);

        $newStatus = RequestStatus::from($data['status']);
        $currentStatus = $operationalRequest->status;

        if (! $currentStatus->canTransitionTo($newStatus)) {
            return response()->json([
                'message' => "Cannot transition from {$currentStatus->label()} to {$newStatus->label()}.",
            ], 422);
        }

        $operationalRequest->statusHistories()->create([
            'organization_id' => $operationalRequest->organization_id,
            'changed_by' => $request->user()->id,
            'from_status' => $currentStatus,
            'to_status' => $newStatus,
        ]);

        $operationalRequest->update(['status' => $newStatus]);

        return response()->json($operationalRequest->fresh(['creator:id,name', 'assignee:id,name']));
    }
}
