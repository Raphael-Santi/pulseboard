<?php

declare(strict_types=1);

namespace App\Checks\Executors;

use App\Checks\CheckExecutor;
use App\Checks\CheckOutcome;
use App\Models\Monitor;
use Illuminate\Support\Facades\Process;

/**
 * Sends a single ICMP echo via the system `ping` binary. The command is passed
 * as an argument array (never a shell string) so a hostile target name cannot
 * inject shell syntax — the lesson carried over from the netpulse project.
 */
final class PingChecker implements CheckExecutor
{
    public function check(Monitor $monitor): CheckOutcome
    {
        $host = (string) $monitor->target;
        $timeout = max(1, $monitor->timeout_sec);
        $start = hrtime(true);

        $result = Process::timeout($timeout + 2)->run([
            'ping', '-c', '1', '-W', (string) $timeout, $host,
        ]);

        $latencyMs = (int) intdiv(hrtime(true) - $start, 1_000_000);

        if ($result->successful()) {
            return CheckOutcome::ok($latencyMs);
        }

        // No target in the message: this reason is public on status pages.
        return CheckOutcome::failed('Хост недоступен');
    }
}
