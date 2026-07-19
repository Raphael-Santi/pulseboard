<?php

declare(strict_types=1);

use App\Models\CheckResult;
use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Hash;

it('builds a demo account with monitors, history and a public status page', function () {
    $this->seed(DemoSeeder::class);

    $user = User::query()->where('email', 'demo@pulseboard.test')->first();
    expect($user)->not->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue();

    expect($user->monitors()->count())->toBe(6)
        ->and($user->monitors()->where('type', 'heartbeat')->count())->toBe(1)
        ->and(CheckResult::query()->count())->toBeGreaterThan(1000);

    $page = StatusPage::query()->where('slug', 'pulseboard-demo')->first();
    expect($page)->not->toBeNull()
        ->and($page->is_public)->toBeTrue()
        ->and($page->monitors()->count())->toBe(6);
});

it('produces a presentable public status page from the seed', function () {
    $this->seed(DemoSeeder::class);

    $response = $this->getJson('/api/status/pulseboard-demo')->assertOk();

    $response->assertJsonPath('title', 'Статус сервисов Acme')
        ->assertJsonCount(6, 'components')
        ->assertJsonCount(90, 'components.0.uptime');

    expect($response->json('incidents'))->not->toBeEmpty();
});

it('seeds monitors that already have recorded latency for charts', function () {
    $this->seed(DemoSeeder::class);

    $monitor = Monitor::query()->where('type', 'http')->firstOrFail();

    expect($monitor->checkResults()->whereNotNull('latency_ms')->exists())->toBeTrue();
});
