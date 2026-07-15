<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MonitorType;
use Database\Factories\MonitorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property MonitorType $type
 * @property string|null $target
 * @property int|null $port
 * @property int $interval_sec
 * @property int $timeout_sec
 * @property int $failure_threshold
 * @property bool $is_paused
 * @property string|null $token
 * @property int|null $grace_sec
 * @property Carbon|null $last_ping_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, CheckResult> $checkResults
 * @property-read Collection<int, Incident> $incidents
 * @property-read Collection<int, AlertChannel> $alertChannels
 * @property-read Collection<int, StatusPage> $statusPages
 */
#[Fillable([
    'name',
    'type',
    'target',
    'port',
    'interval_sec',
    'timeout_sec',
    'failure_threshold',
    'is_paused',
    'grace_sec',
])]
class Monitor extends Model
{
    /** @use HasFactory<MonitorFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MonitorType::class,
            'is_paused' => 'boolean',
            'last_ping_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<CheckResult, $this> */
    public function checkResults(): HasMany
    {
        return $this->hasMany(CheckResult::class);
    }

    /** @return HasMany<Incident, $this> */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /** @return BelongsToMany<AlertChannel, $this> */
    public function alertChannels(): BelongsToMany
    {
        return $this->belongsToMany(AlertChannel::class);
    }

    /** @return BelongsToMany<StatusPage, $this> */
    public function statusPages(): BelongsToMany
    {
        return $this->belongsToMany(StatusPage::class)
            ->withPivot(['display_name', 'sort_order']);
    }
}
