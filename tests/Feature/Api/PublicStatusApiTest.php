<?php

declare(strict_types=1);

use App\Models\CheckResult;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\StatusPage;

it('returns 404 for an unknown slug', function () {
    $this->getJson('/api/status/missing')->assertNotFound();
});

it('does not expose a private status page', function () {
    $page = StatusPage::factory()->hidden()->create(['slug' => 'secret']);

    $this->getJson("/api/status/{$page->slug}")->assertNotFound();
});

it('renders public components with their display names and status', function () {
    $page = StatusPage::factory()->create(['slug' => 'acme', 'title' => 'Acme']);
    $monitor = Monitor::factory()->for($page->user)->create(['name' => 'Internal name']);
    CheckResult::factory()->for($monitor)->create(['checked_at' => now()]);
    $page->monitors()->attach($monitor, ['display_name' => 'Public API', 'sort_order' => 1]);

    $response = $this->getJson('/api/status/acme')->assertOk();

    $response->assertJsonPath('title', 'Acme')
        ->assertJsonPath('overall_status', 'operational')
        ->assertJsonPath('components.0.name', 'Public API')
        ->assertJsonPath('components.0.status', 'operational')
        ->assertJsonCount(90, 'components.0.uptime');
});

it('marks a component down when it has an open incident', function () {
    $page = StatusPage::factory()->create(['slug' => 'acme']);
    $monitor = Monitor::factory()->for($page->user)->create();
    Incident::factory()->for($monitor)->create(['closed_at' => null]);
    $page->monitors()->attach($monitor);

    $this->getJson('/api/status/acme')
        ->assertOk()
        ->assertJsonPath('components.0.status', 'down')
        ->assertJsonPath('overall_status', 'down');
});

it('does not leak monitor internals like the target or token', function () {
    $page = StatusPage::factory()->create(['slug' => 'acme']);
    $monitor = Monitor::factory()->heartbeat()->for($page->user)->create();
    $page->monitors()->attach($monitor);

    $body = $this->getJson('/api/status/acme')->assertOk()->json();

    expect(json_encode($body))->not->toContain($monitor->token)
        ->and(json_encode($body))->not->toContain('user_id');
});

it('lists recent incidents for the page monitors', function () {
    $page = StatusPage::factory()->create(['slug' => 'acme']);
    $monitor = Monitor::factory()->for($page->user)->create();
    Incident::factory()->for($monitor)->create(['cause' => 'Timeout', 'closed_at' => now()]);
    $page->monitors()->attach($monitor);

    $this->getJson('/api/status/acme')
        ->assertOk()
        ->assertJsonPath('incidents.0.cause', 'Timeout');
});
