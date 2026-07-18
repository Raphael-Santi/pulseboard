<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MonitorType;
use App\Jobs\RunCheckJob;
use App\Models\Monitor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Runs every minute from the scheduler. Selects active monitors whose last
 * check is older than their interval and queues a probe for each. "Due" is
 * decided in PHP against the eager-loaded latest check, keeping the query
 * portable across SQLite (tests) and MySQL (production).
 */
#[Signature('monitors:dispatch-due')]
#[Description('Queue checks for every monitor that is due')]
class DispatchDueChecks extends Command
{
    public function handle(): int
    {
        $now = Carbon::now();

        $due = Monitor::query()
            ->where('is_paused', false)
            ->whereIn('type', array_map(
                fn (MonitorType $type): string => $type->value,
                MonitorType::activeCases(),
            ))
            ->with('latestCheck')
            ->get()
            ->filter(fn (Monitor $monitor): bool => $monitor->isDue($now));

        foreach ($due as $monitor) {
            RunCheckJob::dispatch($monitor);
        }

        $this->info("Dispatched {$due->count()} check(s).");

        return self::SUCCESS;
    }
}
