<?php

declare(strict_types=1);

use App\Checks\CheckExecutorFactory;
use App\Jobs\RunCheckJob;
use App\Models\CheckResult;
use App\Models\Incident;
use App\Models\Monitor;
use App\Services\IncidentManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

function recordLatest(Monitor $monitor): void
{
    $latest = $monitor->checkResults()->latest('checked_at')->firstOrFail();
    app(IncidentManager::class)->record($monitor, $latest);
}

it('opens an incident after the failure threshold is reached', function () {
    $monitor = Monitor::factory()->create(['failure_threshold' => 3]);
    CheckResult::factory()->for($monitor)->failed()->count(3)->create();

    recordLatest($monitor);

    expect($monitor->incidents()->whereNull('closed_at')->count())->toBe(1)
        ->and($monitor->incidents()->first()->updates()->count())->toBe(1);
});

it('does not open an incident before the threshold', function () {
    $monitor = Monitor::factory()->create(['failure_threshold' => 3]);
    CheckResult::factory()->for($monitor)->failed()->count(2)->create();

    recordLatest($monitor);

    expect($monitor->incidents()->count())->toBe(0);
});

it('does not open a second incident while one is already open', function () {
    $monitor = Monitor::factory()->create(['failure_threshold' => 1]);
    Incident::factory()->for($monitor)->create(['closed_at' => null]);
    CheckResult::factory()->for($monitor)->failed()->create();

    recordLatest($monitor);

    expect($monitor->incidents()->count())->toBe(1);
});

it('auto-closes the open incident when a check passes', function () {
    $monitor = Monitor::factory()->create();
    $incident = Incident::factory()->for($monitor)->create(['closed_at' => null]);
    CheckResult::factory()->for($monitor)->create();

    recordLatest($monitor);

    expect($incident->refresh()->closed_at)->not->toBeNull()
        ->and($incident->updates()->where('status', 'resolved')->count())->toBe(1);
});

it('opens an incident end to end through the job', function () {
    Http::fake(['*' => Http::response('', 500)]);
    $monitor = Monitor::factory()->create(['failure_threshold' => 1]);

    (new RunCheckJob($monitor))->handle(
        app(CheckExecutorFactory::class),
        app(IncidentManager::class),
    );

    expect($monitor->incidents()->whereNull('closed_at')->count())->toBe(1);
});
