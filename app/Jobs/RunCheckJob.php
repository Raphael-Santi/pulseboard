<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Checks\CheckExecutorFactory;
use App\Models\Monitor;
use App\Services\IncidentManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs one scheduled probe for a monitor, records the result, and lets the
 * incident manager open or close incidents (and fire alerts) in response.
 */
class RunCheckJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Monitor $monitor) {}

    public function handle(CheckExecutorFactory $factory, IncidentManager $incidents): void
    {
        $outcome = $factory->for($this->monitor->type)->check($this->monitor);

        $result = $this->monitor->checkResults()->create([
            'status' => $outcome->status,
            'latency_ms' => $outcome->latencyMs,
            'error' => $outcome->error,
            'checked_at' => now(),
        ]);

        $incidents->record($this->monitor, $result);
    }
}
