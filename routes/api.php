<?php
use App\Http\Controllers\Api\NetworkEventController;
use Illuminate\Support\Facades\Route;

// Authentifié par signature HMAC (pas de session, pas de CSRF).
Route::post('/network/events', NetworkEventController::class)->middleware('throttle:600,1');
