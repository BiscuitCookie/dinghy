<?php

use App\Http\Controllers\Api\ActionItemController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\RetroController;
use App\Http\Controllers\Api\TimerController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/meta', MetaController::class);

    Route::get('/retros', [RetroController::class, 'index']);
    Route::post('/retros', [RetroController::class, 'store']);
    Route::post('/retros/join', [RetroController::class, 'join']);
    Route::get('/retros/{retro}', [RetroController::class, 'show']);
    Route::post('/retros/{retro}/advance', [RetroController::class, 'advancePhase']);

    Route::post('/retros/{retro}/timer', [TimerController::class, 'store']);
    Route::delete('/retros/{retro}/timer', [TimerController::class, 'destroy']);

    Route::post('/columns/{column}/cards', [CardController::class, 'store']);
    Route::delete('/cards/{card}', [CardController::class, 'destroy']);
    Route::post('/cards/{card}/vote', [CardController::class, 'vote']);
    Route::patch('/cards/{card}/move', [CardController::class, 'move']);

    Route::post('/retros/{retro}/action-items', [ActionItemController::class, 'store']);
    Route::patch('/action-items/{item}', [ActionItemController::class, 'update']);
    Route::delete('/action-items/{item}', [ActionItemController::class, 'destroy']);
});
