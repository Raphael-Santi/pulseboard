<?php

declare(strict_types=1);

use App\Jobs\RunCheckJob;
use App\Models\CheckResult;
use App\Models\Monitor;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('dispatches a check for a monitor that has never been checked', function () {
    $monitor = Monitor::factory()->create();

    $this->artisan('monitors:dispatch-due')->assertSuccessful();

    Queue::assertPushed(
        RunCheckJob::class,
        fn (RunCheckJob $job): bool => $job->monitor->is($monitor),
    );
});

it('dispatches a check when the last check is older than the interval', function () {
    $monitor = Monitor::factory()->create(['interval_sec' => 60]);
    CheckResult::factory()->for($monitor)->create(['checked_at' => now()->subMinutes(5)]);

    $this->artisan('monitors:dispatch-due')->assertSuccessful();

    Queue::assertPushed(RunCheckJob::class, 1);
});

it('does not dispatch when the last check is still within the interval', function () {
    $monitor = Monitor::factory()->create(['interval_sec' => 300]);
    CheckResult::factory()->for($monitor)->create(['checked_at' => now()->subSeconds(30)]);

    $this->artisan('monitors:dispatch-due')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('skips paused and heartbeat monitors', function () {
    Monitor::factory()->paused()->create();
    Monitor::factory()->heartbeat()->create();

    $this->artisan('monitors:dispatch-due')->assertSuccessful();

    Queue::assertNothingPushed();
});
