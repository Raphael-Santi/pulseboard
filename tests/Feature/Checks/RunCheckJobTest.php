<?php

declare(strict_types=1);

use App\Checks\CheckExecutorFactory;
use App\Jobs\RunCheckJob;
use App\Models\Monitor;
use Illuminate\Support\Facades\Http;

it('records an ok result for a healthy http monitor', function () {
    Http::fake(['*' => Http::response('OK', 200)]);
    $monitor = Monitor::factory()->create();

    (new RunCheckJob($monitor))->handle(app(CheckExecutorFactory::class));

    $this->assertDatabaseHas('check_results', [
        'monitor_id' => $monitor->id,
        'status' => 'ok',
    ]);
    expect($monitor->checkResults()->sole()->latency_ms)->not->toBeNull();
});

it('records a failed result with an error message on a bad response', function () {
    Http::fake(['*' => Http::response('', 500)]);
    $monitor = Monitor::factory()->create();

    (new RunCheckJob($monitor))->handle(app(CheckExecutorFactory::class));

    $result = $monitor->checkResults()->sole();

    expect($result->status->value)->toBe('failed')
        ->and($result->error)->toContain('500');
});
