<?php

declare(strict_types=1);

use App\Modules\Payments\Presentation\Http\Controllers\Admin\PaymentsAdminController;
use App\Modules\Payments\Presentation\Http\Controllers\PaymentController;
use App\Modules\Payments\Presentation\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payments API routes
|--------------------------------------------------------------------------
*/

// Gateway webhooks — public, authenticated by signature inside the service.
Route::post('payments/webhooks/{gateway}', [WebhookController::class, 'handle']);

// Authenticated customer payment flow.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('payments/gateways', [PaymentController::class, 'gateways']);
    Route::post('payments/initiate', [PaymentController::class, 'initiate']);
    Route::get('payments/{payment}', [PaymentController::class, 'show']);
});

// Admin: gateway configuration, traceability and reconciliation retries.
Route::prefix('admin/payments')
    ->middleware(['auth:sanctum', 'admin', 'permission:manage payments'])
    ->group(function (): void {
        Route::get('gateways', [PaymentsAdminController::class, 'gateways']);
        Route::put('gateways/{gateway}', [PaymentsAdminController::class, 'updateGateway']);
        Route::get('/', [PaymentsAdminController::class, 'payments']);
        Route::post('{payment}/retry', [PaymentsAdminController::class, 'retry']);
    });
