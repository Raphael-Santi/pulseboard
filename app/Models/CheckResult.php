<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CheckStatus;
use Database\Factories\CheckResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $monitor_id
 * @property CheckStatus $status
 * @property int|null $latency_ms
 * @property string|null $error
 * @property Carbon $checked_at
 * @property-read Monitor $monitor
 */
#[Fillable(['status', 'latency_ms', 'error', 'checked_at'])]
class CheckResult extends Model
{
    /** @use HasFactory<CheckResultFactory> */
    use HasFactory;

    /**
     * Check results are immutable events; created_at/updated_at add nothing
     * over checked_at and would bloat the hottest table in the schema.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CheckStatus::class,
            'latency_ms' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Monitor, $this> */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
