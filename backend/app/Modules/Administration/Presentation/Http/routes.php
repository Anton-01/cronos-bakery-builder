<?php

declare(strict_types=1);

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Presentation\Http\Controllers\AccessControlController;
use App\Modules\Administration\Presentation\Http\Controllers\AdminAuthController;
use App\Modules\Administration\Presentation\Http\Controllers\AuditLogController;
use App\Modules\Administration\Presentation\Http\Controllers\DashboardController;
use App\Modules\Administration\Presentation\Http\Controllers\UserManagementController;
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

        // Analytical dashboard (available to every administrator).
        Route::middleware('permission:view dashboard')
            ->get('dashboard', [DashboardController::class, 'index']);

        // Audit trail of every administrative action.
        Route::middleware('permission:view audit')
            ->get('audit-logs', [AuditLogController::class, 'index']);

        // Customer management.
        Route::middleware('permission:manage users')->group(function (): void {
            Route::get('users', [UserManagementController::class, 'index']);
            Route::get('users/{user}', [UserManagementController::class, 'show']);
        });

        // Access control: roles + administrators (Super Admin / Administrador).
        Route::middleware('role:' . AdminRole::SuperAdmin->value . '|' . AdminRole::Administrator->value)
            ->group(function (): void {
                Route::get('roles', [AccessControlController::class, 'roles']);
                Route::get('admins', [AccessControlController::class, 'admins']);
                Route::post('admins', [AccessControlController::class, 'storeAdmin']);
                Route::put('admins/{admin}/roles', [AccessControlController::class, 'assignRoles']);
            });
    });
});
