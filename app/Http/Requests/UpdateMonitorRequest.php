<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Updating a monitor accepts the same payload as creating one; the owning
 * check is enforced by the controller policy on the bound model.
 */
class UpdateMonitorRequest extends StoreMonitorRequest {}
