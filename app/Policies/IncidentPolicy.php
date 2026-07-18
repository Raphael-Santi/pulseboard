<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    /**
     * An incident may only be viewed by the owner of its monitor.
     */
    public function view(User $user, Incident $incident): bool
    {
        return $user->id === $incident->monitor->user_id;
    }

    /**
     * Acknowledging an incident is an update, allowed only for the owner.
     */
    public function update(User $user, Incident $incident): bool
    {
        return $user->id === $incident->monitor->user_id;
    }
}
