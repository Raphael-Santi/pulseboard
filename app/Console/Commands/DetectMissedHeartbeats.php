<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Services\IncidentManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Runs every minute from the scheduler. Opens a "missed heartbeat" incident
 * for any heartbeat monitor whose expected ping did not arrive within its
 * interval plus grace period.
 */
#[Signature('heartbeats:check-missed')]
#[Description('Open incidents for heartbeat monitors that missed their ping')]
class DetectMissedHeartbeats extends Command
{
    public function handle(IncidentManager $incidents): int
    {
        $now = Carbon::now();

        $missed = Monitor::query()
            ->where('type', MonitorType::Heartbeat)
            ->where('is_paused', false)
            ->get()
            ->filter(fn (Monitor $monitor): bool => $monitor->isHeartbeatMissed($now));

        foreach ($missed as $monitor) {
            $incidents->recordMissedHeartbeat($monitor);
        }

        $this->info("Flagged {$missed->count()} missed heartbeat(s).");

        return self::SUCCESS;
    }
}
