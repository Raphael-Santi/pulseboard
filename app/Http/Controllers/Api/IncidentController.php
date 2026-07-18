<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IncidentController extends Controller
{
    /**
     * List a monitor's incidents (newest first) with their update timeline.
     */
    public function index(Monitor $monitor): AnonymousResourceCollection
    {
        $this->authorize('view', $monitor);

        $incidents = $monitor->incidents()
            ->with('updates')
            ->latest('opened_at')
            ->get();

        return IncidentResource::collection($incidents);
    }

    /**
     * Acknowledge an open incident. Acknowledging is idempotent — the first
     * acknowledgement wins and later calls leave the timestamp untouched.
     */
    public function acknowledge(Incident $incident): IncidentResource
    {
        $this->authorize('update', $incident);

        if ($incident->acknowledged_at === null && $incident->closed_at === null) {
            $incident->update(['acknowledged_at' => now()]);
        }

        return new IncidentResource($incident->load('updates'));
    }
}
