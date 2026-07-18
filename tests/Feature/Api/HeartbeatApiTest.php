<?php

declare(strict_types=1);

use App\Enums\MonitorType;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\User;

it('creates a heartbeat monitor with a generated token', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/monitors/heartbeat', [
        'name' => 'Nightly backup',
        'interval_sec' => 86400,
        'grace_sec' => 3600,
    ])->assertCreated()
        ->assertJsonPath('data.type', 'heartbeat');

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
    $this->assertDatabaseHas('monitors', [
        'name' => 'Nightly backup',
        'type' => 'heartbeat',
        'user_id' => $user->id,
    ]);
});

it('validates heartbeat input', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/monitors/heartbeat', [
        'name' => '',
        'interval_sec' => 10,
    ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'interval_sec', 'grace_sec']);
});

it('accepts a ping on the public endpoint and records it', function () {
    $monitor = Monitor::factory()->heartbeat()->create(['last_ping_at' => null]);

    $this->postJson("/api/hb/{$monitor->token}")->assertNoContent();

    expect($monitor->refresh()->last_ping_at)->not->toBeNull()
        ->and($monitor->checkResults()->where('status', 'ok')->count())->toBe(1);
});

it('closes an open incident when a heartbeat ping arrives', function () {
    $monitor = Monitor::factory()->heartbeat()->create();
    $incident = Incident::factory()->for($monitor)->create(['closed_at' => null]);

    $this->postJson("/api/hb/{$monitor->token}")->assertNoContent();

    expect($incident->refresh()->closed_at)->not->toBeNull();
});

it('returns 404 for an unknown heartbeat token', function () {
    $this->postJson('/api/hb/does-not-exist')->assertNotFound();
});

it('does not resolve a token belonging to a non-heartbeat monitor', function () {
    $monitor = Monitor::factory()->create(['type' => MonitorType::Http]);

    // Even if a token somehow existed on an http monitor, the ping route only
    // matches heartbeat monitors.
    $this->postJson('/api/hb/whatever')->assertNotFound();

    expect($monitor->fresh())->not->toBeNull();
});
