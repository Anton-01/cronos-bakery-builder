<?php

declare(strict_types=1);

use App\Shared\Http\Controllers\DevLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Feature-specific endpoints live inside each module and are registered by the
| corresponding module service provider. This file only exposes top-level,
| cross-cutting endpoints.
*/

Route::get('/status', fn () => response()->json([
    'application' => config('app.name'),
    'status' => 'ok',
    'timestamp' => now()->toIso8601String(),
]));

if (config('app.debug')) {
    Route::prefix('dev')->group(function (): void {
        Route::get('logs', [DevLogController::class, 'latest']);
        Route::delete('logs', [DevLogController::class, 'clear']);
    });
}
