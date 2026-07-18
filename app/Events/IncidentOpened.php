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
 * Broadcast when a monitor's incident is opened.
 */
class IncidentOpened implements ShouldBroadcast
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
        return 'incident.opened';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'monitor_id' => $this->monitor->id,
            'incident_id' => $this->incident->id,
            'cause' => $this->incident->cause,
            'opened_at' => $this->incident->opened_at->toIso8601String(),
        ];
    }
}
