<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActionItemRequest;
use App\Http\Resources\ActionItemResource;
use App\Mail\ActionItemAssigned;
use App\Models\ActionItem;
use App\Models\Retro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class ActionItemController extends Controller
{
    public function store(StoreActionItemRequest $request, Retro $retro): JsonResponse
    {
        Gate::authorize('view', $retro);
        $this->abortUnlessEditable($retro);

        $data = $request->validated();

        $item = $retro->actionItems()->create([
            'body'        => trim($data['body']),
            'assignee_id' => $data['assignee_id'] ?? null,
            'position'    => $retro->actionItems()->count(),
        ]);

        if ($item->assignee) {
            Mail::to($item->assignee)->queue(new ActionItemAssigned($item));
        }

        return (new ActionItemResource($item->fresh()->load('assignee')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, ActionItem $item): JsonResponse
    {
        Gate::authorize('view', $item->retro);
        $this->abortUnlessEditable($item->retro);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', ActionItem::STATUSES)],
        ]);

        $item->update(['status' => $data['status']]);

        return (new ActionItemResource($item->fresh()->load('assignee')))->response();
    }

    public function destroy(Request $request, ActionItem $item): Response
    {
        Gate::authorize('advancePhase', $item->retro);

        $item->delete();

        return response()->noContent();
    }

    private function abortUnlessEditable(Retro $retro): void
    {
        abort_unless(
            $retro->phase->isDiscussion(),
            409,
            'Действия доступны только в фазе обсуждения'
        );
    }
}
