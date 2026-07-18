<?php

declare(strict_types=1);

namespace App\Enums;

enum MonitorType: string
{
    case Http = 'http';
    case Tcp = 'tcp';
    case Dns = 'dns';
    case Ping = 'ping';
    case Heartbeat = 'heartbeat';

    /**
     * Types that are probed by an executor on a schedule. Heartbeat monitors
     * are passive (they wait for an inbound ping), so they are excluded.
     *
     * @return list<self>
     */
    public static function activeCases(): array
    {
        return [self::Http, self::Tcp, self::Dns, self::Ping];
    }
}
