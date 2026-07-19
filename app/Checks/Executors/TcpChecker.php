<?php

declare(strict_types=1);

namespace App\Checks\Executors;

use App\Checks\CheckExecutor;
use App\Checks\CheckOutcome;
use App\Models\Monitor;

/**
 * Opens a raw TCP connection to host:port. A successful handshake means up;
 * a refused connection or timeout means down. Latency is measured on a
 * monotonic clock so a wall-clock adjustment cannot produce negative values.
 */
final class TcpChecker implements CheckExecutor
{
    public function check(Monitor $monitor): CheckOutcome
    {
        $errno = 0;
        $errstr = '';
        $start = hrtime(true);

        $connection = @fsockopen(
            (string) $monitor->target,
            (int) $monitor->port,
            $errno,
            $errstr,
            (float) $monitor->timeout_sec,
        );

        if ($connection === false) {
            // errstr can contain the resolved host; keep the public-facing
            // reason generic and expose only the numeric errno.
            return CheckOutcome::failed("Соединение не установлено (errno {$errno})");
        }

        $latencyMs = (int) intdiv(hrtime(true) - $start, 1_000_000);
        fclose($connection);

        return CheckOutcome::ok($latencyMs);
    }
}
