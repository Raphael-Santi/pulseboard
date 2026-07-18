<?php

declare(strict_types=1);

use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\MonitorController;
use App\Http\Controllers\Api\MonitorMetricsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public: external cron pings this with the monitor's secret token.
Route::post('hb/{token}', [HeartbeatController::class, 'ping'])
    ->middleware('throttle:60,1')
    ->name('heartbeat.ping');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::post('monitors/heartbeat', [HeartbeatController::class, 'store'])
        ->name('monitors.heartbeat.store');

    Route::post('monitors/{monitor}/toggle-pause', [MonitorController::class, 'togglePause'])
        ->name('monitors.toggle-pause');

    Route::apiResource('monitors', MonitorController::class);

    Route::get('monitors/{monitor}/metrics', [MonitorMetricsController::class, 'show'])
        ->name('monitors.metrics');

    Route::get('monitors/{monitor}/incidents', [IncidentController::class, 'index'])
        ->name('monitors.incidents.index');

    Route::post('incidents/{incident}/acknowledge', [IncidentController::class, 'acknowledge'])
        ->name('incidents.acknowledge');
});
