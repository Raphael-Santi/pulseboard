<?php

declare(strict_types=1);

use App\Checks\Executors\TcpChecker;
use App\Enums\CheckStatus;
use App\Models\Monitor;

it('reports ok when the port accepts a connection', function () {
    // Bind a throwaway listener on a random free loopback port.
    $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    expect($server)->not->toBeFalse();

    $address = stream_socket_get_name($server, false);
    $port = (int) substr((string) $address, (int) strrpos((string) $address, ':') + 1);

    $monitor = Monitor::factory()->make([
        'target' => '127.0.0.1',
        'port' => $port,
        'timeout_sec' => 2,
    ]);

    $outcome = (new TcpChecker)->check($monitor);

    fclose($server);

    expect($outcome->status)->toBe(CheckStatus::Ok)
        ->and($outcome->latencyMs)->toBeGreaterThanOrEqual(0)
        ->and($outcome->error)->toBeNull();
});

it('reports failed when nothing listens on the port', function () {
    // Grab a port, then immediately release it so the connection is refused.
    $probe = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    $address = stream_socket_get_name($probe, false);
    $port = (int) substr((string) $address, (int) strrpos((string) $address, ':') + 1);
    fclose($probe);

    $monitor = Monitor::factory()->make([
        'target' => '127.0.0.1',
        'port' => $port,
        'timeout_sec' => 1,
    ]);

    $outcome = (new TcpChecker)->check($monitor);

    expect($outcome->status)->toBe(CheckStatus::Failed)
        ->and($outcome->error)->not->toBeNull();
});
