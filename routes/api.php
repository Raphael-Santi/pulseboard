<?php

declare(strict_types=1);

use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\MonitorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::post('monitors/{monitor}/toggle-pause', [MonitorController::class, 'togglePause'])
        ->name('monitors.toggle-pause');

    Route::apiResource('monitors', MonitorController::class);

    Route::get('monitors/{monitor}/incidents', [IncidentController::class, 'index'])
        ->name('monitors.incidents.index');

    Route::post('incidents/{incident}/acknowledge', [IncidentController::class, 'acknowledge'])
        ->name('incidents.acknowledge');
});
