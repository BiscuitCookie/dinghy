<?php

namespace App\Http\Resources;

use App\Models\Retro;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Retro
 */
class RetroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'                 => $this->id,
            'title'              => $this->title,
            'phase'              => $this->phase,
            'is_anonymous'       => $this->is_anonymous,
            'votes_per_user'     => $this->votes_per_user,
            'is_facilitator'     => $user ? $this->isFacilitator($user) : false,
            'invite_code'        => $user && $this->isFacilitator($user) ? $this->invite_code : null,
            'participants_count' => $this->participants_count ?? $this->participants()->count(),
            'cards_count'        => $this->cards_count ?? $this->cards()->count(),
            'created_at'         => $this->created_at->toISOString(),
        ];
    }
}
