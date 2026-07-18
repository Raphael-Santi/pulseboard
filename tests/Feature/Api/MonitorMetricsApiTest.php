<?php

declare(strict_types=1);

use App\Models\CheckResult;
use App\Models\Monitor;
use App\Models\User;

it('requires ownership to read metrics', function () {
    $monitor = Monitor::factory()->create();

    $this->actingAs(User::factory()->create())
        ->getJson("/api/monitors/{$monitor->id}/metrics")
        ->assertForbidden();
});

it('computes uptime as the share of passing checks', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create();

    CheckResult::factory()->for($monitor)->count(9)->create(['checked_at' => now()->subMinutes(10)]);
    CheckResult::factory()->for($monitor)->failed()->create(['checked_at' => now()->subMinutes(10)]);

    $response = $this->actingAs($user)->getJson("/api/monitors/{$monitor->id}/metrics")
        ->assertOk();

    expect($response->json('uptime.24h'))->toEqual(90.0);
});

it('returns null uptime when there are no checks', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create();

    $this->actingAs($user)->getJson("/api/monitors/{$monitor->id}/metrics")
        ->assertOk()
        ->assertJsonPath('uptime.24h', null);
});

it('scopes uptime to the requested window', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create();

    // Outside 24h but inside 7d: a failure two days ago.
    CheckResult::factory()->for($monitor)->failed()->create(['checked_at' => now()->subDays(2)]);
    // Inside 24h: all passing.
    CheckResult::factory()->for($monitor)->count(3)->create(['checked_at' => now()->subHour()]);

    $response = $this->actingAs($user)->getJson("/api/monitors/{$monitor->id}/metrics")
        ->assertOk();

    expect($response->json('uptime.24h'))->toEqual(100.0)
        ->and($response->json('uptime.7d'))->toEqual(75.0);
});

it('downsamples the latency series and averages each bucket', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create();

    // Two checks a second apart collapse into one averaged bucket.
    CheckResult::factory()->for($monitor)->create([
        'latency_ms' => 100,
        'checked_at' => now()->subMinutes(3),
    ]);
    CheckResult::factory()->for($monitor)->create([
        'latency_ms' => 200,
        'checked_at' => now()->subMinutes(3)->addSeconds(1),
    ]);

    $response = $this->actingAs($user)->getJson("/api/monitors/{$monitor->id}/metrics");

    $response->assertOk()
        ->assertJsonPath('latency.window', '24h')
        ->assertJsonCount(1, 'latency.points')
        ->assertJsonPath('latency.points.0.avg_ms', 150);
});

it('accepts the 7d latency window', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create();

    $this->actingAs($user)->getJson("/api/monitors/{$monitor->id}/metrics?window=7d")
        ->assertOk()
        ->assertJsonPath('latency.window', '7d');
});

it('falls back to 24h for an unknown window', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create();

    $this->actingAs($user)->getJson("/api/monitors/{$monitor->id}/metrics?window=bogus")
        ->assertOk()
        ->assertJsonPath('latency.window', '24h');
});
