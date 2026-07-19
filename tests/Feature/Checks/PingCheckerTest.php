<?php

declare(strict_types=1);

use App\Checks\Executors\PingChecker;
use App\Enums\CheckStatus;
use App\Models\Monitor;
use Illuminate\Support\Facades\Process;

it('reports ok when ping exits successfully', function () {
    Process::fake(['*' => Process::result(output: '1 packets transmitted, 1 received')]);

    $monitor = Monitor::factory()->make(['target' => '127.0.0.1', 'timeout_sec' => 2]);

    expect((new PingChecker)->check($monitor)->status)->toBe(CheckStatus::Ok);
});

it('reports failed when ping exits non-zero', function () {
    Process::fake(['*' => Process::result(output: '', errorOutput: '', exitCode: 1)]);

    $monitor = Monitor::factory()->make(['target' => '10.255.255.1', 'timeout_sec' => 1]);

    $outcome = (new PingChecker)->check($monitor);

    expect($outcome->status)->toBe(CheckStatus::Failed)
        ->and($outcome->error)->toContain('недоступен')
        ->and($outcome->error)->not->toContain('10.255.255.1');
});

it('never passes the target through a shell', function () {
    Process::fake(['*' => Process::result(output: 'ok')]);

    $monitor = Monitor::factory()->make(['target' => '127.0.0.1; rm -rf /', 'timeout_sec' => 1]);

    (new PingChecker)->check($monitor);

    Process::assertRan(function ($process): bool {
        // The command is an argument array, so the malicious target stays a
        // single argument and is never interpreted by a shell.
        return is_array($process->command)
            && in_array('127.0.0.1; rm -rf /', $process->command, true);
    });
});
