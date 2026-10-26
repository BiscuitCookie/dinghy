<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['retro_id', 'title', 'color', 'position'])]
class RetroColumn extends Model
{
    public function retro(): BelongsTo
    {
        return $this->belongsTo(Retro::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class)->latest();
    }
}
