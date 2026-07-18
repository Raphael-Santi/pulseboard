<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CheckStatus;
use App\Enums\IncidentUpdateStatus;
use App\Models\AlertChannel;
use App\Models\CheckResult;
use App\Models\Incident;
use App\Models\Monitor;
use App\Notifications\MonitorDownNotification;
use App\Notifications\MonitorRecoveredNotification;
use Illuminate\Notifications\Notification;

/**
 * Turns a stream of check results into incidents. A monitor opens one incident
 * after `failure_threshold` consecutive failures and keeps it open until a
 * successful check closes it. Opening only once per incident is what throttles
 * repeat alerts — a flapping target does not spam its channels.
 */
class IncidentManager
{
    public function record(Monitor $monitor, CheckResult $result): void
    {
        if ($result->status === CheckStatus::Failed) {
            $this->handleFailure($monitor, $result);

            return;
        }

        $this->handleRecovery($monitor);
    }

    private function handleFailure(Monitor $monitor, CheckResult $result): void
    {
        if ($monitor->incidents()->whereNull('closed_at')->exists()) {
            return;
        }

        if (! $this->thresholdReached($monitor)) {
            return;
        }

        $incident = $monitor->incidents()->create([
            'opened_at' => now(),
            'cause' => $result->error ?? 'Check failed',
        ]);

        $incident->updates()->create([
            'status' => IncidentUpdateStatus::Investigating,
            'message' => $result->error ?? 'Monitor is failing its checks.',
        ]);

        $this->notify($monitor, new MonitorDownNotification($incident));
    }

    private function handleRecovery(Monitor $monitor): void
    {
        $incident = $monitor->incidents()
            ->whereNull('closed_at')
            ->latest('opened_at')
            ->first();

        if ($incident === null) {
            return;
        }

        $incident->update(['closed_at' => now()]);

        $incident->updates()->create([
            'status' => IncidentUpdateStatus::Resolved,
            'message' => 'Monitor is responding again.',
        ]);

        $this->notify($monitor, new MonitorRecoveredNotification($incident));
    }

    /**
     * The threshold is met when the most recent `failure_threshold` checks all
     * failed, so a single recovery in between resets the streak.
     */
    private function thresholdReached(Monitor $monitor): bool
    {
        $recent = $monitor->checkResults()
            ->latest('checked_at')
            ->limit($monitor->failure_threshold)
            ->get();

        return $recent->count() >= $monitor->failure_threshold
            && $recent->every(fn (CheckResult $result): bool => $result->status === CheckStatus::Failed);
    }

    private function notify(Monitor $monitor, Notification $notification): void
    {
        $monitor->alertChannels()
            ->where('is_enabled', true)
            ->get()
            ->each(fn (AlertChannel $channel) => $channel->notify($notification));
    }
}
