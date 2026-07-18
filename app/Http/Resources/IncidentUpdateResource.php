<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\IncidentUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin IncidentUpdate
 */
class IncidentUpdateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'message' => $this->message,
            'created_at' => $this->created_at,
        ];
    }
}
