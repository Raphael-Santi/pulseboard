<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMonitorRequest;
use App\Http\Requests\UpdateMonitorRequest;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MonitorController extends Controller
{
    /**
     * List the authenticated user's monitors, newest first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Monitor::class);

        /** @var User $user */
        $user = $request->user();

        $monitors = $user->monitors()
            ->with(['latestCheck', 'openIncidents'])
            ->latest()
            ->get();

        return MonitorResource::collection($monitors);
    }

    /**
     * Store a newly created monitor for the authenticated user.
     */
    public function store(StoreMonitorRequest $request): JsonResponse
    {
        $this->authorize('create', Monitor::class);

        /** @var User $user */
        $user = $request->user();

        $monitor = $user->monitors()->create($request->validated());

        return (new MonitorResource($monitor))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the given monitor.
     */
    public function show(Monitor $monitor): MonitorResource
    {
        $this->authorize('view', $monitor);

        return new MonitorResource($monitor);
    }

    /**
     * Update the given monitor.
     */
    public function update(UpdateMonitorRequest $request, Monitor $monitor): MonitorResource
    {
        $this->authorize('update', $monitor);

        $monitor->update($request->validated());

        return new MonitorResource($monitor);
    }

    /**
     * Remove the given monitor.
     */
    public function destroy(Monitor $monitor): Response
    {
        $this->authorize('delete', $monitor);

        $monitor->delete();

        return response()->noContent();
    }

    /**
     * Toggle whether the monitor is paused.
     */
    public function togglePause(Monitor $monitor): MonitorResource
    {
        $this->authorize('update', $monitor);

        $monitor->update(['is_paused' => ! $monitor->is_paused]);

        return new MonitorResource($monitor);
    }
}
