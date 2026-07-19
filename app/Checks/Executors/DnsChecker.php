<?php

declare(strict_types=1);

namespace App\Checks\Executors;

use App\Checks\CheckExecutor;
use App\Checks\CheckOutcome;
use App\Models\Monitor;

/**
 * Resolves the target hostname through the system resolver (which honors both
 * /etc/hosts and DNS). At least one A record means up; no resolution is down.
 */
final class DnsChecker implements CheckExecutor
{
    public function check(Monitor $monitor): CheckOutcome
    {
        $host = (string) $monitor->target;
        $start = hrtime(true);

        $addresses = gethostbynamel($host);

        $latencyMs = (int) intdiv(hrtime(true) - $start, 1_000_000);

        if ($addresses === false || $addresses === []) {
            // No target in the message: this reason is public on status pages.
            return CheckOutcome::failed('Не удалось разрешить имя');
        }

        return CheckOutcome::ok($latencyMs);
    }
}
