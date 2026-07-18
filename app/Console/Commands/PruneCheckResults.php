<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CheckResult;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Trims the check_results history — the hottest, fastest-growing table — to a
 * retention window. Runs daily from the scheduler. The lesson from netpulse:
 * an append-only checks table needs pruning or it grows without bound.
 */
#[Signature('checks:prune {--days=30 : Delete check results older than this many days}')]
#[Description('Delete check results beyond the retention window')]
class PruneCheckResults extends Command
{
    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = Carbon::now()->subDays($days);

        $deleted = CheckResult::query()
            ->where('checked_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} check result(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
