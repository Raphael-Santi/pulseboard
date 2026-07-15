<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Every non-API path is handled by the Vue SPA. Excluded prefixes must keep
// their native behavior: /api/* (JSON 404, not HTML), /up (framework health
// endpoint), /storage and /build (static files; a miss must be a real 404).
Route::view('/{any?}', 'app')
    ->where('any', '^(?!api/|up$|storage/|build/).*')
    ->name('spa');
