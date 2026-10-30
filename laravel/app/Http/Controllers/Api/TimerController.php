<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTimerRequest;
use App\Http\Resources\RetroResource;
use App\Models\Retro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TimerController extends Controller
{
    public function store(StoreTimerRequest $request, Retro $retro): JsonResponse
    {
        Gate::authorize('manageTimer', $retro);

        $retro->update([
            'timer_ends_at' => now()->addMinutes($request->validated('minutes')),
        ]);

        return (new RetroResource($retro->fresh()))->response();
    }

    public function destroy(Request $request, Retro $retro): JsonResponse
    {
        Gate::authorize('manageTimer', $retro);

        $retro->update(['timer_ends_at' => null]);

        return (new RetroResource($retro->fresh()))->response();
    }
}
