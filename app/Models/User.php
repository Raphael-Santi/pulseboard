<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property-read Collection<int, Monitor> $monitors
 * @property-read Collection<int, AlertChannel> $alertChannels
 * @property-read Collection<int, StatusPage> $statusPages
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return HasMany<Monitor, $this> */
    public function monitors(): HasMany
    {
        return $this->hasMany(Monitor::class);
    }

    /** @return HasMany<AlertChannel, $this> */
    public function alertChannels(): HasMany
    {
        return $this->hasMany(AlertChannel::class);
    }

    /** @return HasMany<StatusPage, $this> */
    public function statusPages(): HasMany
    {
        return $this->hasMany(StatusPage::class);
    }
}
