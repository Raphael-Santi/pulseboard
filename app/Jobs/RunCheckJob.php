<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Checks\CheckExecutorFactory;
use App\Models\Monitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs one scheduled probe for a monitor and records the result. Reacting to
 * that result (opening/closing incidents) is added in the next module.
 */
class RunCheckJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Monitor $monitor) {}

    public function handle(CheckExecutorFactory $factory): void
    {
        $outcome = $factory->for($this->monitor->type)->check($this->monitor);

        $this->monitor->checkResults()->create([
            'status' => $outcome->status,
            'latency_ms' => $outcome->latencyMs,
            'error' => $outcome->error,
            'checked_at' => now(),
        ]);
    }
}
