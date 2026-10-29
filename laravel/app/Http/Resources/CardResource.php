<?php

namespace App\Http\Resources;

use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Card
 */
class CardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $retro = $this->column->retro;

        return [
            'id'              => $this->id,
            'retro_column_id' => $this->retro_column_id,
            'body'            => $this->body,
            'votes_count'     => $this->whenCounted('votes') ?? $this->votes()->count(),
            'voted_by_me'     => $this->when((bool) $request->user(), fn () => $this->votes->contains('user_id', $request->user()->id)),
            'is_mine'         => $request->user() ? $this->user_id === $request->user()->id : false,
            'author_name'     => $retro->is_anonymous ? null : $this->author->name,
        ];
    }
}
