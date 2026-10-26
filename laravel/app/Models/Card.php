<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['retro_column_id', 'user_id', 'body'])]
class Card extends Model
{
    public function column(): BelongsTo
    {
        return $this->belongsTo(RetroColumn::class, 'retro_column_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
