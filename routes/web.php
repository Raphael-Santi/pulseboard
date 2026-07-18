<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

// Every non-API path is handled by the Vue SPA. Excluded prefixes must keep
// their native behavior: /api/* (JSON 404, not HTML), /up (framework health
// endpoint), /sanctum/* (CSRF cookie), /broadcasting/* (channel auth),
// /storage and /build (static files).
Route::view('/{any?}', 'app')
    ->where('any', '^(?!api/|up$|sanctum/|broadcasting/|storage/|build/).*')
    ->name('spa');
