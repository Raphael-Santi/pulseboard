<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\CheckResult;
use App\Models\Monitor;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast after every recorded check so the dashboard can flip a monitor's
 * live status without polling.
 */
class CheckResultRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Monitor $monitor,
        public readonly CheckResult $result,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('monitors.'.$this->monitor->user_id);
    }

    public function broadcastAs(): string
    {
        return 'check.recorded';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'monitor_id' => $this->monitor->id,
            'status' => $this->result->status->value,
            'latency_ms' => $this->result->latency_ms,
            'checked_at' => $this->result->checked_at->toIso8601String(),
        ];
    }
}
