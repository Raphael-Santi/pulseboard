<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a monitor's open incident is auto-closed by a passing check.
 */
class IncidentClosed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Monitor $monitor,
        public readonly Incident $incident,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('monitors.'.$this->monitor->user_id);
    }

    public function broadcastAs(): string
    {
        return 'incident.closed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'monitor_id' => $this->monitor->id,
            'incident_id' => $this->incident->id,
            'closed_at' => $this->incident->closed_at?->toIso8601String(),
        ];
    }
}
