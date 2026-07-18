<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Typed pivot for the monitor ↔ status page relationship, carrying the
 * per-page display name and ordering.
 *
 * @property string|null $display_name
 * @property int $sort_order
 */
class MonitorStatusPage extends Pivot
{
    protected $table = 'monitor_status_page';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
