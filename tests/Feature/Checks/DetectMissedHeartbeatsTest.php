<?php

declare(strict_types=1);

use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('opens an incident when a heartbeat is overdue', function () {
    $monitor = Monitor::factory()->heartbeat()->create([
        'interval_sec' => 3600,
        'grace_sec' => 300,
        'last_ping_at' => now()->subHours(3),
    ]);

    $this->artisan('heartbeats:check-missed')->assertSuccessful();

    expect($monitor->incidents()->whereNull('closed_at')->count())->toBe(1);
});

it('flags a never-pinged heartbeat created before the deadline', function () {
    $monitor = Monitor::factory()->heartbeat()->create([
        'interval_sec' => 3600,
        'grace_sec' => 300,
        'last_ping_at' => null,
        'created_at' => now()->subDay(),
    ]);

    $this->artisan('heartbeats:check-missed')->assertSuccessful();

    expect($monitor->incidents()->count())->toBe(1);
});

it('does not flag a recently pinged heartbeat', function () {
    $monitor = Monitor::factory()->heartbeat()->create([
        'interval_sec' => 3600,
        'grace_sec' => 300,
        'last_ping_at' => now()->subMinutes(5),
    ]);

    $this->artisan('heartbeats:check-missed')->assertSuccessful();

    expect($monitor->incidents()->count())->toBe(0);
});

it('does not double-open when an incident is already open', function () {
    $monitor = Monitor::factory()->heartbeat()->create([
        'interval_sec' => 3600,
        'grace_sec' => 300,
        'last_ping_at' => now()->subHours(3),
    ]);
    Incident::factory()->for($monitor)->create(['closed_at' => null]);

    $this->artisan('heartbeats:check-missed')->assertSuccessful();

    expect($monitor->incidents()->count())->toBe(1);
});

it('ignores paused and non-heartbeat monitors', function () {
    Monitor::factory()->heartbeat()->paused()->create(['last_ping_at' => now()->subDay()]);
    Monitor::factory()->create(['last_ping_at' => now()->subDay()]);

    $this->artisan('heartbeats:check-missed')->assertSuccessful();

    expect(Incident::query()->count())->toBe(0);
});
