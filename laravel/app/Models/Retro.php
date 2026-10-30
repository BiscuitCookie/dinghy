<?php

namespace App\Models;

use App\Enums\RetroPhase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

/**
 * @property RetroPhase $phase
 * @property Carbon|null $timer_ends_at
 */
#[Fillable(['user_id', 'title', 'invite_code', 'phase', 'is_anonymous', 'votes_per_user', 'timer_ends_at'])]
class Retro extends Model
{
    public const ROLE_FACILITATOR = 'facilitator';

    public const ROLE_PARTICIPANT = 'participant';

    protected $attributes = [
        'phase' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'phase'         => RetroPhase::class,
            'is_anonymous'  => 'boolean',
            'timer_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Retro $retro): void {
            $retro->invite_code ??= strtoupper(Str::random(8));
        });
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'retro_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<RetroColumn, $this> */
    public function columns(): HasMany
    {
        return $this->hasMany(RetroColumn::class)->orderBy('position');
    }

    /** @return HasManyThrough<Card, RetroColumn, $this> */
    public function cards(): HasManyThrough
    {
        return $this->hasManyThrough(Card::class, RetroColumn::class);
    }

    /** @return HasMany<ActionItem, $this> */
    public function actionItems(): HasMany
    {
        return $this->hasMany(ActionItem::class)->orderBy('position');
    }

    public function isFacilitator(User $user): bool
    {
        return $this->user_id === $user->id
            || $this->participants()->where('user_id', $user->id)->wherePivot('role', self::ROLE_FACILITATOR)->exists();
    }

    public function isParticipant(User $user): bool
    {
        return $this->user_id === $user->id
            || $this->participants()->where('user_id', $user->id)->exists();
    }

    public function advancePhase(): void
    {
        if ($next = $this->phase->next()) {
            $this->update(['phase' => $next]);
        }
    }
}
