<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AlertChannelType;
use Database\Factories\AlertChannelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property AlertChannelType $type
 * @property string $destination
 * @property bool $is_enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, Monitor> $monitors
 */
#[Fillable(['type', 'destination', 'is_enabled'])]
class AlertChannel extends Model
{
    /** @use HasFactory<AlertChannelFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AlertChannelType::class,
            'is_enabled' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Monitor, $this> */
    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class);
    }

    /**
     * Route mail notifications to this channel's address when it is an email
     * channel; other channel types return null so the mail driver is skipped.
     */
    public function routeNotificationForMail(): ?string
    {
        return $this->type === AlertChannelType::Email ? $this->destination : null;
    }

    /**
     * Route Telegram notifications to this channel's chat id when it is a
     * Telegram channel.
     */
    public function routeNotificationForTelegram(): ?string
    {
        return $this->type === AlertChannelType::Telegram ? $this->destination : null;
    }
}
