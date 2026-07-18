<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// The scheduler picks which monitors are due each minute; the executors run in
// queue workers, so a slow target never blocks the tick.
Schedule::command('monitors:dispatch-due')->everyMinute()->withoutOverlapping();

Schedule::command('checks:prune')->dailyAt('03:00');
