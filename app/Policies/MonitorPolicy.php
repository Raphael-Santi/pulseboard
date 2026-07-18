<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Monitor;
use App\Models\User;

class MonitorPolicy
{
    /**
     * Any authenticated user may list their own monitors.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * A monitor may only be viewed by its owner.
     */
    public function view(User $user, Monitor $monitor): bool
    {
        return $user->id === $monitor->user_id;
    }

    /**
     * Any authenticated user may create monitors.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * A monitor may only be updated by its owner.
     */
    public function update(User $user, Monitor $monitor): bool
    {
        return $user->id === $monitor->user_id;
    }

    /**
     * A monitor may only be deleted by its owner.
     */
    public function delete(User $user, Monitor $monitor): bool
    {
        return $user->id === $monitor->user_id;
    }
}
