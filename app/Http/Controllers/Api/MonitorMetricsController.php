<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Monitor;
use App\Services\MonitorMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitorMetricsController extends Controller
{
    /** Latency chart resolution — averages collapse to at most this many points. */
    private const LATENCY_POINTS = 48;

    public function show(Request $request, Monitor $monitor, MonitorMetrics $metrics): JsonResponse
    {
        $this->authorize('view', $monitor);

        $now = CarbonImmutable::now();

        $uptime = [
            '24h' => $metrics->uptime($monitor, $now->subDay()),
            '7d' => $metrics->uptime($monitor, $now->subWeek()),
            '30d' => $metrics->uptime($monitor, $now->subDays(30)),
        ];

        // Latency is only offered for the shorter windows to keep the payload
        // small; 30 days of per-minute checks is for the uptime figure only.
        $window = in_array($request->string('window')->value(), ['24h', '7d'], true)
            ? $request->string('window')->value()
            : '24h';

        $since = $window === '7d' ? $now->subWeek() : $now->subDay();

        return response()->json([
            'uptime' => $uptime,
            'latency' => [
                'window' => $window,
                'points' => $metrics->latencySeries($monitor, $since, $now, self::LATENCY_POINTS),
            ],
        ]);
    }
}
