<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Monitor;
use App\Models\StatusPage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Owner-facing view of a status page, including its attached monitors and the
 * pivot metadata used to render the public page.
 *
 * @mixin StatusPage
 */
class StatusPageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'is_public' => $this->is_public,
            'monitors' => $this->whenLoaded('monitors', fn (): array => $this->monitors->map(
                fn (Monitor $monitor): array => [
                    'id' => $monitor->id,
                    'name' => $monitor->name,
                    'display_name' => $monitor->pivot->display_name,
                    'sort_order' => $monitor->pivot->sort_order,
                ],
            )->all()),
            'created_at' => $this->created_at,
        ];
    }
}
