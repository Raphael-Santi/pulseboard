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
}
