<?php

declare(strict_types=1);

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Presentation\Http\Controllers\AdminAuthController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Administration API routes (independent admin guard)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function (): void {
    // --- Guest --------------------------------------------------------------
    Route::post('login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('password/forgot', [AdminAuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
    Route::post('password/reset', [AdminAuthController::class, 'resetPassword'])->middleware('throttle:6,1');

    // --- Authenticated administrators --------------------------------------
    Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
        Route::get('me', [AdminAuthController::class, 'me']);
        Route::post('logout', [AdminAuthController::class, 'logout']);

        // Example role-gated endpoint (Super Admin / Administrador only).
        Route::middleware('role:' . AdminRole::SuperAdmin->value . '|' . AdminRole::Administrator->value)
            ->get('dashboard', fn (Request $request): JsonResponse => response()->json([
                'message' => 'Welcome to the administration dashboard.',
                'admin' => $request->user()->only(['id', 'name', 'email']),
            ]));

        // Example permission-gated endpoint (granular control).
        Route::middleware('permission:manage products')
            ->get('catalog/overview', fn (): JsonResponse => response()->json([
                'message' => 'Catalog management overview.',
            ]));
    });
});
