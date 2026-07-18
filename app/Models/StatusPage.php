<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StatusPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $slug
 * @property string $title
 * @property bool $is_public
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, Monitor> $monitors
 */
#[Fillable(['slug', 'title', 'is_public'])]
class StatusPage extends Model
{
    /** @use HasFactory<StatusPageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Monitor, $this, MonitorStatusPage> */
    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class)
            ->using(MonitorStatusPage::class)
            ->withPivot(['display_name', 'sort_order'])
            ->orderByPivot('sort_order');
    }
}
