<?php

declare(strict_types=1);

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Presentation\Http\Controllers\AccessControlController;
use App\Modules\Administration\Presentation\Http\Controllers\AdminAuthController;
use App\Modules\Administration\Presentation\Http\Controllers\AdminProfileController;
use App\Modules\Administration\Presentation\Http\Controllers\AuditLogController;
use App\Modules\Administration\Presentation\Http\Controllers\DashboardController;
use App\Modules\Administration\Presentation\Http\Controllers\HealthController;
use App\Modules\Administration\Presentation\Http\Controllers\TwoFactorController;
use App\Modules\Administration\Presentation\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Administration API routes (independent admin guard)
|--------------------------------------------------------------------------
*/

// Public observability probe for load balancers / uptime monitoring.
Route::get('health', [HealthController::class, 'health']);

Route::prefix('admin')->group(function (): void {
    // --- Guest --------------------------------------------------------------
    Route::post('login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('password/forgot', [AdminAuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
    Route::post('password/reset', [AdminAuthController::class, 'resetPassword'])->middleware('throttle:6,1');

    // --- Authenticated administrators --------------------------------------
    Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
        Route::get('me', [AdminAuthController::class, 'me']);
        Route::post('logout', [AdminAuthController::class, 'logout']);

        // Self-service profile: personal data, avatar (MinIO), password,
        // notification preferences and active devices (Sanctum sessions).
        Route::put('profile', [AdminProfileController::class, 'update']);
        Route::post('profile/avatar', [AdminProfileController::class, 'uploadAvatar']);
        Route::delete('profile/avatar', [AdminProfileController::class, 'deleteAvatar']);
        Route::put('profile/password', [AdminProfileController::class, 'updatePassword']);
        Route::put('profile/notifications', [AdminProfileController::class, 'updateNotificationSettings']);
        Route::get('profile/sessions', [AdminProfileController::class, 'sessions']);
        Route::delete('profile/sessions/{token}', [AdminProfileController::class, 'revokeSession'])->whereNumber('token');
        Route::post('profile/sessions/revoke-others', [AdminProfileController::class, 'revokeOtherSessions']);

        // Two-factor authentication enrolment.
        Route::post('2fa/enable', [TwoFactorController::class, 'enable']);
        Route::post('2fa/confirm', [TwoFactorController::class, 'confirm']);
        Route::post('2fa/disable', [TwoFactorController::class, 'disable']);

        // Operational metrics snapshot.
        Route::middleware('permission:view dashboard')
            ->get('metrics', [HealthController::class, 'metrics']);

        // Analytical dashboard (available to every administrator).
        Route::middleware('permission:view dashboard')
            ->get('dashboard', [DashboardController::class, 'index']);

        // Audit trail of every administrative action.
        Route::middleware('permission:view audit')
            ->get('audit-logs', [AuditLogController::class, 'index']);

        // Customer management.
        Route::middleware('permission:manage users')->group(function (): void {
            Route::get('users', [UserManagementController::class, 'index']);
            Route::post('users', [UserManagementController::class, 'store']);
            Route::get('users/{user}', [UserManagementController::class, 'show']);
            Route::put('users/{user}', [UserManagementController::class, 'update']);
            Route::delete('users/{user}', [UserManagementController::class, 'destroy']);
            Route::post('users/{user}/suspend', [UserManagementController::class, 'suspend']);
            Route::post('users/{user}/reactivate', [UserManagementController::class, 'reactivate']);
            Route::post('users/{user}/impersonate', [UserManagementController::class, 'impersonate']);
            Route::post('users/{user}/revoke-sessions', [UserManagementController::class, 'revokeSessions']);
            Route::post('users/{user}/send-password-reset', [UserManagementController::class, 'sendPasswordReset']);
            Route::get('users/{user}/sessions', [UserManagementController::class, 'sessions']);
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
