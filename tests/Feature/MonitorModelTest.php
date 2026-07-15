<?php

declare(strict_types=1);

use App\Enums\CheckStatus;
use App\Enums\MonitorType;
use App\Models\AlertChannel;
use App\Models\CheckResult;
use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\User;

it('belongs to its owner and casts attributes', function () {
    $monitor = Monitor::factory()->create();

    expect($monitor->user)->toBeInstanceOf(User::class)
        ->and($monitor->type)->toBe(MonitorType::Http)
        ->and($monitor->is_paused)->toBeFalse();
});

it('creates heartbeat monitors with a token and grace period', function () {
    $monitor = Monitor::factory()->heartbeat()->create();

    expect($monitor->type)->toBe(MonitorType::Heartbeat)
        ->and($monitor->token)->not->toBeNull()
        ->and($monitor->grace_sec)->toBe(300)
        ->and($monitor->target)->toBeNull();
});

it('records check results with a status enum and timestamp', function () {
    $monitor = Monitor::factory()->create();
    $result = CheckResult::factory()->for($monitor)->failed()->create();

    expect($monitor->checkResults()->count())->toBe(1)
        ->and($result->status)->toBe(CheckStatus::Failed)
        ->and($result->error)->not->toBeNull()
        ->and($result->checked_at)->not->toBeNull();
});

it('removes dependent check results when a monitor is deleted', function () {
    $monitor = Monitor::factory()
        ->has(CheckResult::factory()->count(3), 'checkResults')
        ->create();

    $monitor->delete();

    expect(CheckResult::query()->count())->toBe(0);
});

it('links alert channels through the pivot table', function () {
    $monitor = Monitor::factory()->create();
    $channel = AlertChannel::factory()->for($monitor->user)->create();

    $monitor->alertChannels()->attach($channel);

    expect($monitor->alertChannels()->count())->toBe(1)
        ->and($channel->monitors()->first()?->is($monitor))->toBeTrue();
});

it('orders status page monitors by the pivot sort order', function () {
    $page = StatusPage::factory()->create();
    [$first, $second] = Monitor::factory()->count(2)->for($page->user)->create();

    $page->monitors()->attach($second, ['display_name' => 'API', 'sort_order' => 1]);
    $page->monitors()->attach($first, ['display_name' => 'Site', 'sort_order' => 2]);

    expect($page->monitors->pluck('id')->all())->toBe([$second->id, $first->id])
        ->and($page->monitors->first()?->pivot?->display_name)->toBe('API');
});
