<?php

declare(strict_types=1);

namespace App\Checks\Executors;

use App\Checks\CheckExecutor;
use App\Checks\CheckOutcome;
use App\Models\Monitor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Probes an HTTP(S) endpoint. A 2xx/3xx response counts as up; 4xx/5xx and
 * connection failures (including timeouts) count as down.
 */
final class HttpChecker implements CheckExecutor
{
    public function check(Monitor $monitor): CheckOutcome
    {
        $start = hrtime(true);

        try {
            $response = Http::timeout($monitor->timeout_sec)
                ->get((string) $monitor->target);
        } catch (ConnectionException $e) {
            return CheckOutcome::failed($e->getMessage());
        }

        $latencyMs = $this->elapsedMs($start);

        if ($response->successful() || $response->redirect()) {
            return CheckOutcome::ok($latencyMs);
        }

        return CheckOutcome::failed("HTTP {$response->status()}", $latencyMs);
    }

    private function elapsedMs(int $start): int
    {
        return (int) intdiv(hrtime(true) - $start, 1_000_000);
    }
}
