<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CheckStatus;
use App\Models\Monitor;
use Carbon\CarbonInterface;

/**
 * Aggregates check history into dashboard metrics. Uptime uses portable COUNT
 * queries; the latency series is downsampled in PHP into fixed time buckets so
 * the same code runs on SQLite (tests) and MySQL (production).
 */
class MonitorMetrics
{
    /**
     * Uptime percentage over a window, or null when there are no checks yet.
     */
    public function uptime(Monitor $monitor, CarbonInterface $since): ?float
    {
        $total = $monitor->checkResults()
            ->where('checked_at', '>=', $since)
            ->count();

        if ($total === 0) {
            return null;
        }

        $ok = $monitor->checkResults()
            ->where('checked_at', '>=', $since)
            ->where('status', CheckStatus::Ok)
            ->count();

        return round($ok / $total * 100, 2);
    }

    /**
     * Average latency downsampled into at most `$points` time buckets.
     *
     * @return list<array{t: string, avg_ms: int}>
     */
    public function latencySeries(
        Monitor $monitor,
        CarbonInterface $since,
        CarbonInterface $now,
        int $points,
    ): array {
        $results = $monitor->checkResults()
            ->where('checked_at', '>=', $since)
            ->whereNotNull('latency_ms')
            ->orderBy('checked_at')
            ->get(['latency_ms', 'checked_at']);

        if ($results->isEmpty()) {
            return [];
        }

        $span = max(1, $now->getTimestamp() - $since->getTimestamp());
        $bucketSeconds = max(1, (int) ceil($span / $points));

        /** @var array<int, array{sum: int, count: int}> $buckets */
        $buckets = [];

        foreach ($results as $result) {
            $offset = max(0, $result->checked_at->getTimestamp() - $since->getTimestamp());
            $index = intdiv($offset, $bucketSeconds);

            $buckets[$index]['sum'] = ($buckets[$index]['sum'] ?? 0) + (int) $result->latency_ms;
            $buckets[$index]['count'] = ($buckets[$index]['count'] ?? 0) + 1;
        }

        ksort($buckets);

        $series = [];

        foreach ($buckets as $index => $bucket) {
            $series[] = [
                't' => $since->copy()->addSeconds($index * $bucketSeconds)->toIso8601String(),
                'avg_ms' => (int) round($bucket['sum'] / $bucket['count']),
            ];
        }

        return $series;
    }
}
