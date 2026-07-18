<?php

declare(strict_types=1);

use App\Models\Monitor;
use App\Models\User;

it('requires authentication', function () {
    $this->getJson('/api/monitors')->assertUnauthorized();
});

it('lists only the authenticated user monitors', function () {
    $user = User::factory()->create();
    Monitor::factory()->count(2)->for($user)->create();
    Monitor::factory()->count(3)->create();

    $this->actingAs($user)->getJson('/api/monitors')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('creates a monitor for the authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/monitors', [
        'name' => 'My site',
        'type' => 'http',
        'target' => 'https://example.com',
        'interval_sec' => 60,
        'timeout_sec' => 10,
        'failure_threshold' => 3,
    ])->assertCreated()
        ->assertJsonPath('data.name', 'My site')
        ->assertJsonPath('data.type', 'http')
        ->assertJsonPath('data.is_paused', false);

    $this->assertDatabaseHas('monitors', [
        'name' => 'My site',
        'user_id' => $user->id,
    ]);
});

it('validates required fields and the enum type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/monitors', [
        'name' => '',
        'type' => 'carrier-pigeon',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'type', 'target', 'interval_sec']);
});

it('rejects heartbeat monitors on the standard create endpoint', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/monitors', [
        'name' => 'HB',
        'type' => 'heartbeat',
        'target' => 'x',
        'interval_sec' => 60,
        'timeout_sec' => 10,
        'failure_threshold' => 3,
    ])->assertUnprocessable()->assertJsonValidationErrors('type');
});

it('rejects a timeout that is not shorter than the interval', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/monitors', [
        'name' => 'My site',
        'type' => 'http',
        'target' => 'https://example.com',
        'interval_sec' => 60,
        'timeout_sec' => 60,
        'failure_threshold' => 3,
    ])->assertUnprocessable()->assertJsonValidationErrors('timeout_sec');
});

it('requires a port for tcp monitors', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/monitors', [
        'name' => 'DB',
        'type' => 'tcp',
        'target' => 'db.example.com',
        'interval_sec' => 60,
        'timeout_sec' => 10,
        'failure_threshold' => 3,
    ])->assertUnprocessable()->assertJsonValidationErrors('port');
});

it('forbids viewing another user monitor', function () {
    $monitor = Monitor::factory()->create();

    $this->actingAs(User::factory()->create())
        ->getJson("/api/monitors/{$monitor->id}")
        ->assertForbidden();
});

it('forbids updating another user monitor', function () {
    $monitor = Monitor::factory()->create();

    $this->actingAs(User::factory()->create())
        ->putJson("/api/monitors/{$monitor->id}", [
            'name' => 'Hijacked',
            'type' => 'http',
            'target' => 'https://evil.example.com',
            'interval_sec' => 60,
            'timeout_sec' => 10,
            'failure_threshold' => 3,
        ])->assertForbidden();
});

it('updates an owned monitor', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create(['name' => 'Old']);

    $this->actingAs($user)->putJson("/api/monitors/{$monitor->id}", [
        'name' => 'New name',
        'type' => 'http',
        'target' => 'https://example.org',
        'interval_sec' => 120,
        'timeout_sec' => 15,
        'failure_threshold' => 5,
    ])->assertOk()->assertJsonPath('data.name', 'New name');

    expect($monitor->refresh()->interval_sec)->toBe(120);
});

it('deletes an owned monitor', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create();

    $this->actingAs($user)->deleteJson("/api/monitors/{$monitor->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('monitors', ['id' => $monitor->id]);
});

it('toggles the pause state', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create(['is_paused' => false]);

    $this->actingAs($user)->postJson("/api/monitors/{$monitor->id}/toggle-pause")
        ->assertOk()
        ->assertJsonPath('data.is_paused', true);

    expect($monitor->refresh()->is_paused)->toBeTrue();
});
