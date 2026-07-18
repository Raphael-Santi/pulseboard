<?php

declare(strict_types=1);

use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\Monitor;
use App\Models\User;

it('lists incidents with their timeline for an owned monitor', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create();
    $incident = Incident::factory()->for($monitor)->create();
    IncidentUpdate::factory()->for($incident)->count(2)->create();

    $this->actingAs($user)->getJson("/api/monitors/{$monitor->id}/incidents")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonCount(2, 'data.0.updates')
        ->assertJsonPath('data.0.status', 'open');
});

it('forbids listing incidents of another user monitor', function () {
    $monitor = Monitor::factory()->create();

    $this->actingAs(User::factory()->create())
        ->getJson("/api/monitors/{$monitor->id}/incidents")
        ->assertForbidden();
});

it('acknowledges an open incident', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create();
    $incident = Incident::factory()->for($monitor)->create(['closed_at' => null]);

    $this->actingAs($user)->postJson("/api/incidents/{$incident->id}/acknowledge")
        ->assertOk()
        ->assertJsonPath('data.status', 'acknowledged');

    expect($incident->refresh()->acknowledged_at)->not->toBeNull();
});

it('forbids acknowledging another user incident', function () {
    $incident = Incident::factory()->create();

    $this->actingAs(User::factory()->create())
        ->postJson("/api/incidents/{$incident->id}/acknowledge")
        ->assertForbidden();
});
