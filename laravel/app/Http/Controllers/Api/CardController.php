<?php

namespace App\Http\Controllers\Api;

use App\Enums\RetroPhase;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCardRequest;
use App\Http\Resources\CardResource;
use App\Models\Card;
use App\Models\RetroColumn;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CardController extends Controller
{
    public function store(StoreCardRequest $request, RetroColumn $column): JsonResponse
    {
        $retro = $column->retro;
        Gate::authorize('view', $retro);
        abort_unless($retro->phase === RetroPhase::Collect, 409, 'Сбор карточек завершён');

        $data = $request->validated();

        $card = $column->cards()->create([
            'user_id' => $request->user()->id,
            'body'    => trim($data['body']),
        ]);

        return (new CardResource($this->reload($card)))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, Card $card): Response
    {
        $retro = $card->column->retro;
        Gate::authorize('view', $retro);
        abort_unless(
            $card->user_id === $request->user()->id || $retro->isFacilitator($request->user()),
            403,
            'Можно удалять только свои карточки'
        );

        $card->delete();

        return response()->noContent();
    }

    public function vote(Request $request, Card $card): JsonResponse
    {
        $retro = $card->column->retro;
        Gate::authorize('view', $retro);
        abort_unless($retro->phase === RetroPhase::Vote, 409, 'Сейчас не фаза голосования');

        $existing = $card->votes()->where('user_id', $request->user()->id)->first();

        if ($existing) {
            $existing->delete();

            return (new CardResource($this->reload($card)))->response();
        }

        $myVotes = Vote::where('user_id', $request->user()->id)
            ->whereIn('card_id', $retro->cards()->select('cards.id'))
            ->count();

        abort_if($myVotes >= $retro->votes_per_user, 409, 'Лимит голосов исчерпан');

        $card->votes()->create(['user_id' => $request->user()->id]);

        return (new CardResource($this->reload($card)))->response();
    }

    private function reload(Card $card): Card
    {
        return $card->fresh()->load('votes', 'author')->loadCount('votes');
    }
}
