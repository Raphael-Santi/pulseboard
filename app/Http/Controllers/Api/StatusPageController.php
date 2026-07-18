<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStatusPageRequest;
use App\Http\Requests\SyncStatusPageMonitorsRequest;
use App\Http\Requests\UpdateStatusPageRequest;
use App\Http\Resources\StatusPageResource;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class StatusPageController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', StatusPage::class);

        /** @var User $user */
        $user = $request->user();

        return StatusPageResource::collection($user->statusPages()->latest()->get());
    }

    public function store(StoreStatusPageRequest $request): JsonResponse
    {
        $this->authorize('create', StatusPage::class);

        /** @var User $user */
        $user = $request->user();

        $page = $user->statusPages()->create($request->validated());

        return (new StatusPageResource($page))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(StatusPage $statusPage): StatusPageResource
    {
        $this->authorize('view', $statusPage);

        return new StatusPageResource($statusPage->load('monitors'));
    }

    public function update(UpdateStatusPageRequest $request, StatusPage $statusPage): StatusPageResource
    {
        $this->authorize('update', $statusPage);

        $statusPage->update($request->validated());

        return new StatusPageResource($statusPage);
    }

    public function destroy(StatusPage $statusPage): Response
    {
        $this->authorize('delete', $statusPage);

        $statusPage->delete();

        return response()->noContent();
    }

    /**
     * Replace the monitors shown on a page, carrying their pivot metadata.
     */
    public function syncMonitors(
        SyncStatusPageMonitorsRequest $request,
        StatusPage $statusPage,
    ): StatusPageResource {
        $this->authorize('update', $statusPage);

        /** @var list<array{id: int, display_name?: string|null, sort_order?: int|null}> $monitors */
        $monitors = $request->validated('monitors');

        $pivot = [];
        foreach ($monitors as $monitor) {
            $pivot[$monitor['id']] = [
                'display_name' => $monitor['display_name'] ?? null,
                'sort_order' => $monitor['sort_order'] ?? 0,
            ];
        }

        $statusPage->monitors()->sync($pivot);

        return new StatusPageResource($statusPage->load('monitors'));
    }
}
