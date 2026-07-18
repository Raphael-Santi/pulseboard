<?php

declare(strict_types=1);

use App\Broadcasting\MonitorsChannel;
use Illuminate\Support\Facades\Broadcast;

// Each user has one private channel carrying live updates for all their
// monitors. Authorization lives in the channel class so it can be unit-tested.
Broadcast::channel('monitors.{userId}', MonitorsChannel::class);
