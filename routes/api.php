<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/part-movements', [\App\Http\Controllers\Api\PartMovementsController::class, 'index'])
    ->middleware('google.script');

Route::get('/pick-command', fn () => Cache::pull('pick_command', ''));
