<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateRetro;
use App\Http\Controllers\Controller;
use App\Http\Requests\JoinRetroRequest;
use App\Http\Requests\StoreRetroRequest;
use App\Http\Resources\ActionItemResource;
use App\Http\Resources\CardResource;
use App\Http\Resources\RetroResource;
use App\Models\Retro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RetroController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $owned = $user->retros()->withCount(['participants', 'cards'])->latest()->get();
        $joined = $user->joinedRetros()->where('retros.user_id', '!=', $user->id)
            ->withCount(['participants', 'cards'])->latest()->get();

        return response()->json([
            'owned'  => RetroResource::collection($owned),
            'joined' => RetroResource::collection($joined),
        ]);
    }

    public function store(StoreRetroRequest $request, CreateRetro $createRetro): JsonResponse
    {
        $retro = $createRetro($request->user(), $request->validatedRetroData());

        return (new RetroResource($retro))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Retro $retro): JsonResponse
    {
        Gate::authorize('view', $retro);

        $retro->loadCount(['participants', 'cards']);

        $columns = $retro->columns()
            ->with(['cards' => fn ($query) => $query->with('votes', 'author')->withCount('votes')->latest()])
            ->get();

        $inDiscussion = $retro->phase->isDiscussion();

        return response()->json([
            'retro'   => new RetroResource($retro),
            'columns' => $columns->map(fn ($column) => [
                'id'    => $column->id,
                'title' => $column->title,
                'color' => $column->color,
                'cards' => CardResource::collection($column->cards),
            ])->values(),
            'participants' => $inDiscussion
                ? $retro->participants()->select('users.id', 'users.name')->get()
                    ->map(fn ($participant) => ['id' => $participant->id, 'name' => $participant->name])
                : [],
            'action_items' => $inDiscussion
                ? ActionItemResource::collection($retro->actionItems()->with('assignee')->get())
                : [],
        ]);
    }

    public function join(JoinRetroRequest $request): JsonResponse
    {
        $retro = Retro::where('invite_code', strtoupper($request->validated('invite_code')))->firstOrFail();

        if (! $retro->isParticipant($request->user())) {
            $retro->participants()->attach($request->user()->id, ['role' => Retro::ROLE_PARTICIPANT]);
        }

        return (new RetroResource($retro->fresh()))->response()->setStatusCode(200);
    }

    public function advancePhase(Request $request, Retro $retro): JsonResponse
    {
        Gate::authorize('advancePhase', $retro);

        $retro->advancePhase();

        return (new RetroResource($retro->fresh()))->response();
    }
}
