<?php

declare(strict_types=1);

use App\Enums\IncidentUpdateStatus;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\Monitor;

it('opens against a monitor and stays open until closed', function () {
    $incident = Incident::factory()->create();

    expect($incident->monitor)->toBeInstanceOf(Monitor::class)
        ->and($incident->opened_at)->not->toBeNull()
        ->and($incident->closed_at)->toBeNull();
});

it('can be acknowledged and closed via factory states', function () {
    $incident = Incident::factory()->acknowledged()->closed()->create();

    expect($incident->acknowledged_at)->not->toBeNull()
        ->and($incident->closed_at)->not->toBeNull();
});

it('keeps a timeline of updates with status enums', function () {
    $incident = Incident::factory()->create();
    IncidentUpdate::factory()->for($incident)->count(2)->create();
    $resolved = IncidentUpdate::factory()->for($incident)->resolved()->create();

    expect($incident->updates()->count())->toBe(3)
        ->and($resolved->status)->toBe(IncidentUpdateStatus::Resolved);
});

it('removes updates when the incident is deleted', function () {
    $incident = Incident::factory()
        ->has(IncidentUpdate::factory()->count(2), 'updates')
        ->create();

    $incident->delete();

    expect(IncidentUpdate::query()->count())->toBe(0);
});
