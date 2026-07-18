<?php

declare(strict_types=1);

use App\Broadcasting\MonitorsChannel;
use App\Checks\CheckExecutorFactory;
use App\Events\CheckResultRecorded;
use App\Events\IncidentClosed;
use App\Events\IncidentOpened;
use App\Jobs\RunCheckJob;
use App\Models\CheckResult;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\User;
use App\Services\IncidentManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

it('broadcasts a check result on the owner private channel', function () {
    $monitor = Monitor::factory()->create();
    $result = CheckResult::factory()->for($monitor)->create();

    $event = new CheckResultRecorded($monitor, $result);

    expect($event->broadcastOn()->name)->toBe('private-monitors.'.$monitor->user_id)
        ->and($event->broadcastAs())->toBe('check.recorded')
        ->and($event->broadcastWith())->toMatchArray([
            'monitor_id' => $monitor->id,
            'status' => $result->status->value,
        ]);
});

it('dispatches the check event when a check runs', function () {
    Event::fake([CheckResultRecorded::class]);
    Http::fake(['*' => Http::response('OK', 200)]);
    $monitor = Monitor::factory()->create();

    (new RunCheckJob($monitor))->handle(
        app(CheckExecutorFactory::class),
        app(IncidentManager::class),
    );

    Event::assertDispatched(CheckResultRecorded::class);
});

it('dispatches incident events as incidents open and close', function () {
    Event::fake([IncidentOpened::class, IncidentClosed::class]);
    $monitor = Monitor::factory()->create(['failure_threshold' => 1]);

    CheckResult::factory()->for($monitor)->failed()->create();
    app(IncidentManager::class)->record($monitor, $monitor->checkResults()->latest('checked_at')->firstOrFail());
    Event::assertDispatched(IncidentOpened::class);

    CheckResult::factory()->for($monitor)->create();
    app(IncidentManager::class)->record($monitor, $monitor->checkResults()->latest('checked_at')->firstOrFail());
    Event::assertDispatched(IncidentClosed::class);
});

it('authorizes the owner on their monitors channel and rejects others', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $channel = new MonitorsChannel;

    expect($channel->join($user, $user->id))->toBeTrue()
        ->and($channel->join($user, $other->id))->toBeFalse();
});

it('keeps the incident close payload aligned with the model', function () {
    $monitor = Monitor::factory()->create();
    $incident = Incident::factory()->for($monitor)->create(['closed_at' => now()]);

    $event = new IncidentClosed($monitor, $incident);

    expect($event->broadcastAs())->toBe('incident.closed')
        ->and($event->broadcastWith())->toMatchArray([
            'monitor_id' => $monitor->id,
            'incident_id' => $incident->id,
        ]);
});
