<?php

declare(strict_types=1);

use App\Modules\Orders\Presentation\Http\Controllers\AddressController;
use App\Modules\Orders\Presentation\Http\Controllers\Admin\OrderAdminController;
use App\Modules\Orders\Presentation\Http\Controllers\BranchController;
use App\Modules\Orders\Presentation\Http\Controllers\CartController;
use App\Modules\Orders\Presentation\Http\Controllers\CheckoutController;
use App\Modules\Orders\Presentation\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Orders API routes
|--------------------------------------------------------------------------
*/

// Public: list of pickup branches (sucursales).
Route::get('branches', [BranchController::class, 'index']);

/*
 * All cart, checkout, address and order endpoints require an authenticated
 * customer — purchases are not allowed for guests.
 */
Route::middleware('auth:sanctum')->group(function (): void {
    // Cart.
    Route::get('cart', [CartController::class, 'show']);
    Route::post('cart/items', [CartController::class, 'store']);
    Route::put('cart/items/{item}', [CartController::class, 'update']);
    Route::delete('cart/items/{item}', [CartController::class, 'destroyItem']);
    Route::delete('cart', [CartController::class, 'clear']);

    // Addresses.
    Route::get('addresses', [AddressController::class, 'index']);
    Route::post('addresses', [AddressController::class, 'store']);
    Route::put('addresses/{address}', [AddressController::class, 'update']);
    Route::delete('addresses/{address}', [AddressController::class, 'destroy']);

    // Checkout + order history.
    Route::post('checkout', [CheckoutController::class, 'store']);
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
});

// Admin order management: list + status transitions (fire automations).
Route::prefix('admin/orders')
    ->middleware(['auth:sanctum', 'admin', 'permission:update order status'])
    ->group(function (): void {
        Route::get('/', [OrderAdminController::class, 'index']);
        Route::put('{order}/status', [OrderAdminController::class, 'updateStatus']);
    });
