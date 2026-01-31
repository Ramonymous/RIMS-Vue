<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('users', \App\Http\Controllers\UserController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('parts/template', [\App\Http\Controllers\PartsController::class, 'exportTemplate'])
        ->name('parts.template');
    Route::post('parts/import', [\App\Http\Controllers\PartsController::class, 'import'])
        ->name('parts.import');

    Route::resource('parts', \App\Http\Controllers\PartsController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('stock', [\App\Http\Controllers\StockController::class, 'index'])
        ->name('stock.index');
    Route::get('stock/export', [\App\Http\Controllers\StockController::class, 'export'])
        ->name('stock.export');

    Route::resource('receivings', \App\Http\Controllers\ReceivingsController::class)
        ->except(['show']);

    Route::post('receivings/{receiving}/cancel', [\App\Http\Controllers\ReceivingsController::class, 'cancel'])
        ->name('receivings.cancel');
    Route::post('receivings/{receiving}/gr', [\App\Http\Controllers\ReceivingsController::class, 'updateGrStatus'])
        ->name('receivings.gr');

    Route::resource('outgoings', \App\Http\Controllers\OutgoingsController::class)
        ->except(['show']);

    Route::post('outgoings/{outgoing}/cancel', [\App\Http\Controllers\OutgoingsController::class, 'cancel'])
        ->name('outgoings.cancel');
    Route::post('outgoings/{outgoing}/gi', [\App\Http\Controllers\OutgoingsController::class, 'updateGiStatus'])
        ->name('outgoings.gi');

    Route::resource('requests', \App\Http\Controllers\RequestsController::class)
        ->except(['show', 'destroy']);

    Route::post('requests/{request}/cancel', [\App\Http\Controllers\RequestsController::class, 'cancel'])
        ->name('requests.cancel');
    Route::post('request-items/{requestItem}/supply', [\App\Http\Controllers\RequestsController::class, 'supply'])
        ->name('request-items.supply');
    Route::post('request-items/{requestItem}/pick-command', [\App\Http\Controllers\RequestsController::class, 'pickCommand'])
        ->name('request-items.pick-command');
});

require __DIR__.'/settings.php';
