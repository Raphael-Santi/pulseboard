<?php

declare(strict_types=1);

namespace App\Checks;

use App\Checks\Executors\DnsChecker;
use App\Checks\Executors\HttpChecker;
use App\Checks\Executors\PingChecker;
use App\Checks\Executors\TcpChecker;
use App\Enums\MonitorType;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Resolves the right executor for a monitor type from the service container,
 * so each checker keeps its own injectable dependencies.
 */
final class CheckExecutorFactory
{
    public function __construct(private readonly Container $container) {}

    public function for(MonitorType $type): CheckExecutor
    {
        $executor = match ($type) {
            MonitorType::Http => HttpChecker::class,
            MonitorType::Tcp => TcpChecker::class,
            MonitorType::Dns => DnsChecker::class,
            MonitorType::Ping => PingChecker::class,
            MonitorType::Heartbeat => throw new InvalidArgumentException(
                'Heartbeat monitors are checked passively, not by an executor.',
            ),
        };

        return $this->container->make($executor);
    }
}
