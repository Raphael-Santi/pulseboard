<?php

declare(strict_types=1);

use App\Checks\Executors\DnsChecker;
use App\Enums\CheckStatus;
use App\Models\Monitor;

it('resolves a name that exists via the system resolver', function () {
    // localhost is guaranteed to resolve through /etc/hosts, no network needed.
    $monitor = Monitor::factory()->make(['target' => 'localhost']);

    expect((new DnsChecker)->check($monitor)->status)->toBe(CheckStatus::Ok);
});

it('fails on a name that cannot resolve', function () {
    // .invalid is reserved by RFC 6761 to never resolve.
    $monitor = Monitor::factory()->make(['target' => 'nonexistent-host.invalid']);

    $outcome = (new DnsChecker)->check($monitor);

    expect($outcome->status)->toBe(CheckStatus::Failed)
        ->and($outcome->error)->toContain('разрешить')
        // The target must not leak into the reason (it is shown publicly).
        ->and($outcome->error)->not->toContain('nonexistent-host.invalid');
});
