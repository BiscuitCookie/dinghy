<?php

namespace App\Http\Controllers\Api;

use App\Enums\RetroPhase;
use App\Http\Controllers\Controller;
use App\Http\Requests\MoveCardRequest;
use App\Http\Requests\StoreCardRequest;
use App\Http\Resources\CardResource;
use App\Models\Card;
use App\Models\Retro;
use App\Models\RetroColumn;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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
            'user_id'  => $request->user()->id,
            'body'     => trim($data['body']),
            'position' => (int) $column->cards()->max('position') + 1,
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

        $result = DB::transaction(function () use ($request, $card, $retro) {
            $lockedRetro = Retro::lockForUpdate()->find($retro->id);

            $existing = $card->votes()->where('user_id', $request->user()->id)->first();
            if ($existing) {
                $existing->delete();

                return $card;
            }

            $myVotes = Vote::where('user_id', $request->user()->id)
                ->whereIn('card_id', $lockedRetro->cards()->select('cards.id'))
                ->count();

            abort_if($myVotes >= $lockedRetro->votes_per_user, 409, 'Лимит голосов исчерпан');

            return $card->votes()->create(['user_id' => $request->user()->id])->card;
        });

        return (new CardResource($this->reload($result)))->response();
    }

    public function move(MoveCardRequest $request, Card $card): JsonResponse
    {
        $retro = $card->column->retro;
        Gate::authorize('view', $retro);
        abort_unless(
            $retro->phase === RetroPhase::Collect || $retro->isFacilitator($request->user()),
            403,
            'Перемещать карточки можно в фазе сбора (или фасилитатору)'
        );

        $data = $request->validated();
        $targetColumn = RetroColumn::findOrFail($data['retro_column_id']);
        abort_unless($targetColumn->retro_id === $retro->id, 422, 'Колонка из другого ретро');

        DB::transaction(function () use ($card, $targetColumn, $data) {
            $oldColumnId = $card->retro_column_id;
            $card->update([
                'retro_column_id' => $targetColumn->id,
                'position'        => $data['position'],
            ]);

            foreach (array_unique([$oldColumnId, $targetColumn->id]) as $columnId) {
                Card::where('retro_column_id', $columnId)
                    ->orderBy('position')
                    ->orderBy('id')
                    ->pluck('id')
                    ->each(fn ($id, $index) => Card::whereKey($id)->update(['position' => $index]));
            }
        });

        return (new CardResource($this->reload($card)))->response();
    }

    private function reload(Card $card): Card
    {
        return $card->fresh()->load('votes', 'author')->loadCount('votes');
    }
}
