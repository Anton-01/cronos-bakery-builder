<?php

declare(strict_types=1);

use App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin\OptionController;
use App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin\OptionRuleController;
use App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin\OptionValueController;
use App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin\ProductController;
use App\Modules\ProductBuilder\Presentation\Http\Controllers\ConfiguratorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Product Builder API routes
|--------------------------------------------------------------------------
*/

// --- Public configurator (consumed by the Vue frontend) -------------------
Route::prefix('product-builder')->group(function (): void {
    Route::get('products', [ConfiguratorController::class, 'index']);
    Route::get('products/{slug}', [ConfiguratorController::class, 'show']);
    Route::post('products/{slug}/quote', [ConfiguratorController::class, 'quote']);
});

// --- Admin management (admin guard + manage products permission) -----------
Route::prefix('admin/product-builder')
    ->middleware(['auth:sanctum', 'admin', 'permission:manage products'])
    ->group(function (): void {
        Route::apiResource('products', ProductController::class)->parameters(['products' => 'product']);

        // Options.
        Route::post('products/{product}/options', [OptionController::class, 'store']);
        Route::put('products/{product}/options/{option}', [OptionController::class, 'update']);
        Route::delete('products/{product}/options/{option}', [OptionController::class, 'destroy']);

        // Option values.
        Route::post('products/{product}/options/{option}/values', [OptionValueController::class, 'store']);
        Route::put('products/{product}/options/{option}/values/{value}', [OptionValueController::class, 'update']);
        Route::delete('products/{product}/options/{option}/values/{value}', [OptionValueController::class, 'destroy']);

        // Conditional rules.
        Route::post('products/{product}/rules', [OptionRuleController::class, 'store']);
        Route::delete('products/{product}/rules/{rule}', [OptionRuleController::class, 'destroy']);
    });
