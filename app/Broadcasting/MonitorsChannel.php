<?php

declare(strict_types=1);

namespace App\Broadcasting;

use App\Models\User;

/**
 * Authorizes the private `monitors.{userId}` channel: a user may only listen
 * to updates for their own monitors.
 */
class MonitorsChannel
{
    public function join(User $user, int $userId): bool
    {
        return $user->id === $userId;
    }
}
