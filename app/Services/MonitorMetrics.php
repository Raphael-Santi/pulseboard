<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CheckStatus;
use App\Models\Monitor;
use Carbon\CarbonImmutable;
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

    /**
     * Uptime percentage per calendar day for the last `$days` days, oldest
     * first. Days without any checks report null. Grouping uses the portable
     * SQL `date()` function, which exists on both SQLite and MySQL.
     *
     * @return list<array{date: string, uptime: float|null}>
     */
    public function dailyUptime(Monitor $monitor, int $days): array
    {
        $start = CarbonImmutable::today()->subDays($days - 1);

        $rows = $monitor->checkResults()
            ->where('checked_at', '>=', $start)
            ->toBase()
            ->selectRaw('date(checked_at) as day, count(*) as total, sum(case when status = ? then 1 else 0 end) as ok_count', [
                CheckStatus::Ok->value,
            ])
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $series = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->addDays($offset)->toDateString();
            $row = $rows->get($date);

            $total = (int) ($row->total ?? 0);
            $ok = (int) ($row->ok_count ?? 0);

            $series[] = [
                'date' => $date,
                'uptime' => $total > 0 ? round($ok / $total * 100, 2) : null,
            ];
        }

        return $series;
    }
}
