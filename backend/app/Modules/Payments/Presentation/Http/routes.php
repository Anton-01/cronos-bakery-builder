<?php

declare(strict_types=1);

use App\Modules\Payments\Presentation\Http\Controllers\Admin\PaymentGatewayController;
use App\Modules\Payments\Presentation\Http\Controllers\Admin\TransactionController;
use App\Modules\Payments\Presentation\Http\Controllers\PaymentController;
use App\Modules\Payments\Presentation\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payments API routes
|--------------------------------------------------------------------------
*/

// Gateway webhooks — public, authenticated by cryptographic signature.
// Each configured gateway instance has its own URL (driver + id), so the
// correct per-brand secret is always applied.
Route::post('payments/webhooks/{driver}/{gateway}', [WebhookController::class, 'handle'])
    ->whereNumber('gateway');

// Authenticated customer payment flow.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('payments/gateways', [PaymentController::class, 'gateways']);
    Route::post('payments/initiate', [PaymentController::class, 'initiate']);
    Route::get('payments/{transaction}', [PaymentController::class, 'show'])->whereNumber('transaction');
});

// Admin: gateway management + transaction centre.
Route::prefix('admin/payments')
    ->middleware(['auth:sanctum', 'admin', 'permission:manage payments'])
    ->group(function (): void {
        Route::get('drivers', [PaymentGatewayController::class, 'drivers']);
        Route::get('gateways', [PaymentGatewayController::class, 'index']);
        Route::post('gateways', [PaymentGatewayController::class, 'store']);
        Route::put('gateways/{gateway}', [PaymentGatewayController::class, 'update']);
        Route::delete('gateways/{gateway}', [PaymentGatewayController::class, 'destroy']);

        Route::get('transactions', [TransactionController::class, 'index']);
        Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
        Route::post('transactions/{transaction}/refund', [TransactionController::class, 'refund']);
        Route::post('transactions/{transaction}/retry', [TransactionController::class, 'retry']);
    });
