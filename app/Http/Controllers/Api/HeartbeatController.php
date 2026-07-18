<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\CheckStatus;
use App\Enums\MonitorType;
use App\Events\CheckResultRecorded;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHeartbeatRequest;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use App\Models\User;
use App\Services\IncidentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class HeartbeatController extends Controller
{
    /**
     * Create a heartbeat monitor with a freshly generated ping token.
     */
    public function store(StoreHeartbeatRequest $request): JsonResponse
    {
        $this->authorize('create', Monitor::class);

        /** @var User $user */
        $user = $request->user();

        $monitor = new Monitor([
            'name' => $request->validated('name'),
            'type' => MonitorType::Heartbeat,
            'interval_sec' => $request->integer('interval_sec'),
            'grace_sec' => $request->integer('grace_sec'),
        ]);
        $monitor->token = Str::random(48);
        $user->monitors()->save($monitor);

        return (new MonitorResource($monitor))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Public endpoint hit by an external cron. Records a passing check and
     * closes any open incident. Unknown tokens yield a 404.
     */
    public function ping(string $token, IncidentManager $incidents): Response
    {
        $monitor = Monitor::query()
            ->where('type', MonitorType::Heartbeat)
            ->where('token', $token)
            ->firstOrFail();

        $now = now();

        $result = $monitor->checkResults()->create([
            'status' => CheckStatus::Ok,
            'latency_ms' => null,
            'error' => null,
            'checked_at' => $now,
        ]);

        // last_ping_at is a system field (not mass-assignable), so set it directly.
        $monitor->last_ping_at = $now;
        $monitor->save();

        CheckResultRecorded::dispatch($monitor, $result);
        $incidents->record($monitor, $result);

        return response()->noContent();
    }
}
