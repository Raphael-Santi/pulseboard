<?php

declare(strict_types=1);

use App\Checks\Executors\HttpChecker;
use App\Enums\CheckStatus;
use App\Models\Monitor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('reports ok on a 2xx response', function () {
    Http::fake(['*' => Http::response('OK', 200)]);

    $monitor = Monitor::factory()->make(['target' => 'https://example.com', 'timeout_sec' => 5]);

    expect((new HttpChecker)->check($monitor)->status)->toBe(CheckStatus::Ok);
});

it('treats a 3xx redirect as up', function () {
    Http::fake(['*' => Http::response('', 301)]);

    $monitor = Monitor::factory()->make(['target' => 'https://example.com', 'timeout_sec' => 5]);

    expect((new HttpChecker)->check($monitor)->status)->toBe(CheckStatus::Ok);
});

it('reports failed on a 5xx response and keeps the status in the error', function () {
    Http::fake(['*' => Http::response('', 503)]);

    $monitor = Monitor::factory()->make(['target' => 'https://example.com', 'timeout_sec' => 5]);

    $outcome = (new HttpChecker)->check($monitor);

    expect($outcome->status)->toBe(CheckStatus::Failed)
        ->and($outcome->error)->toContain('503');
});

it('reports failed with a target-free reason when the connection fails', function () {
    // A real cURL error embeds the URL; the reason must not leak it publicly.
    Http::fake(fn () => throw new ConnectionException('cURL error 6: Could not resolve host: secret.internal'));

    $monitor = Monitor::factory()->make(['target' => 'https://secret.internal', 'timeout_sec' => 1]);

    $outcome = (new HttpChecker)->check($monitor);

    expect($outcome->status)->toBe(CheckStatus::Failed)
        ->and($outcome->error)->toContain('подключиться')
        ->and($outcome->error)->not->toContain('secret.internal');
});
