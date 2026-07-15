<?php

declare(strict_types=1);

namespace App\Enums;

enum CheckStatus: string
{
    case Ok = 'ok';
    case Failed = 'failed';
}
