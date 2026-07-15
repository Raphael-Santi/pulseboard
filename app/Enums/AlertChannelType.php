<?php

declare(strict_types=1);

namespace App\Enums;

enum AlertChannelType: string
{
    case Email = 'email';
    case Telegram = 'telegram';
}
