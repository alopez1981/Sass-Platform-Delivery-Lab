<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Request;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched over RabbitMQ (default queue connection, see config/queue.php)
 * whenever a Request is created. Demonstrates the async notification path
 * described in the project brief: this is real background work, not a
 * synchronous side effect of the HTTP request that created it.
 */
class NotifyRequestCreated implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(public Request $request) {}

    public function handle(): void
    {
        $recipients = $this->request->assigned_to
            ? User::whereKey($this->request->assigned_to)->get()
            : User::where('organization_id', $this->request->organization_id)
                ->whereIn('role', [UserRole::Administrator, UserRole::Manager])
                ->get();

        foreach ($recipients as $recipient) {
            Notification::create([
                'organization_id' => $this->request->organization_id,
                'user_id' => $recipient->id,
                'type' => 'request.created',
                'data' => [
                    'request_id' => $this->request->id,
                    'title' => $this->request->title,
                ],
            ]);
        }
    }
}
