<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Incident
 */
class IncidentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'monitor_id' => $this->monitor_id,
            'status' => $this->derivedStatus(),
            'cause' => $this->cause,
            'opened_at' => $this->opened_at,
            'acknowledged_at' => $this->acknowledged_at,
            'closed_at' => $this->closed_at,
            'updates' => IncidentUpdateResource::collection($this->whenLoaded('updates')),
        ];
    }

    /**
     * Incident status is derived from its timestamps rather than stored.
     */
    private function derivedStatus(): string
    {
        return match (true) {
            $this->closed_at !== null => 'closed',
            $this->acknowledged_at !== null => 'acknowledged',
            default => 'open',
        };
    }
}
