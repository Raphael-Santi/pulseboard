<?php

declare(strict_types=1);

use App\Models\CheckResult;
use App\Models\Monitor;

it('deletes check results older than the retention window', function () {
    $monitor = Monitor::factory()->create();
    CheckResult::factory()->for($monitor)->create(['checked_at' => now()->subDays(40)]);
    CheckResult::factory()->for($monitor)->create(['checked_at' => now()->subDays(5)]);

    $this->artisan('checks:prune', ['--days' => 30])->assertSuccessful();

    expect(CheckResult::query()->count())->toBe(1);
});

it('keeps everything when nothing is older than the window', function () {
    $monitor = Monitor::factory()->create();
    CheckResult::factory()->for($monitor)->count(3)->create(['checked_at' => now()->subDay()]);

    $this->artisan('checks:prune', ['--days' => 30])->assertSuccessful();

    expect(CheckResult::query()->count())->toBe(3);
});
