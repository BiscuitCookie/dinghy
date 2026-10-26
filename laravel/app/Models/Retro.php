<?php

namespace App\Models;

use App\Enums\RetroPhase;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'title', 'invite_code', 'phase', 'is_anonymous', 'votes_per_user'])]
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
            'phase'        => RetroPhase::class,
            'is_anonymous' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Retro $retro): void {
            $retro->invite_code ??= strtoupper(Str::random(8));
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'retro_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function columns(): HasMany
    {
        return $this->hasMany(RetroColumn::class)->orderBy('position');
    }

    public function cards(): HasManyThrough
    {
        return $this->hasManyThrough(Card::class, RetroColumn::class);
    }

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
