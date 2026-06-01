<?php

declare(strict_types=1);

use App\Modules\Catalog\Presentation\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catalog API routes
|--------------------------------------------------------------------------
| Loaded by CatalogServiceProvider under the "api" middleware + "/api" prefix.
*/

Route::prefix('catalog')->group(function (): void {
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('products', [ProductController::class, 'store']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);
    });
});
