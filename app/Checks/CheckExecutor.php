<?php

declare(strict_types=1);

namespace App\Checks;

use App\Models\Monitor;

/**
 * A strategy that knows how to probe one kind of monitor target.
 */
interface CheckExecutor
{
    public function check(Monitor $monitor): CheckOutcome;
}
