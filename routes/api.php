<?php

use App\Http\Controllers\TimeEntryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Control de Horas: inicio de cronómetro vía API (protegido por Gate).
Route::middleware('auth:sanctum')
    ->post('/time-entries/start', [TimeEntryController::class, 'start']);
