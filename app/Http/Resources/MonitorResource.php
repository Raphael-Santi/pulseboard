<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Monitor
 */
class MonitorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'target' => $this->target,
            'port' => $this->port,
            'interval_sec' => $this->interval_sec,
            'timeout_sec' => $this->timeout_sec,
            'failure_threshold' => $this->failure_threshold,
            'is_paused' => $this->is_paused,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
