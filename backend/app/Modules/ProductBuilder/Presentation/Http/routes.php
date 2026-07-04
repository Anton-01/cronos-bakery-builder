<?php

declare(strict_types=1);

use App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin\OptionController;
use App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin\OptionRuleController;
use App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin\OptionTemplateController;
use App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin\OptionTemplateValueController;
use App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin\OptionValueController;
use App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin\ProductController;
use App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin\ProductImageController;
use App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin\ProductOptionLinkController;
use App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin\ProductPreviewController;
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

    // Tokenized storefront preview: the token (minted by an admin) is the only
    // credential, so drafts can be viewed in the public layout without a session.
    Route::get('preview/{token}', [ProductPreviewController::class, 'show']);
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

        // Option Templates (global, independent of products).
        Route::get('option-templates', [OptionTemplateController::class, 'index']);
        Route::post('option-templates', [OptionTemplateController::class, 'store']);
        Route::put('option-templates/{template}', [OptionTemplateController::class, 'update']);
        Route::delete('option-templates/{template}', [OptionTemplateController::class, 'destroy']);

        // Option Template Values.
        Route::post('option-templates/{template}/values', [OptionTemplateValueController::class, 'store']);
        Route::put('option-templates/{template}/values/{value}', [OptionTemplateValueController::class, 'update']);
        Route::delete('option-templates/{template}/values/{value}', [OptionTemplateValueController::class, 'destroy']);

        // Product-Option Links.
        Route::get('products/{product}/option-links', [ProductOptionLinkController::class, 'index']);
        Route::post('products/{product}/option-links', [ProductOptionLinkController::class, 'store']);
        Route::put('products/{product}/option-links/{link}', [ProductOptionLinkController::class, 'update']);
        Route::delete('products/{product}/option-links/{link}', [ProductOptionLinkController::class, 'destroy']);


        // Product Images.
        Route::post('products/{product}/images', [ProductImageController::class, 'store']);
        Route::put('products/{product}/images/{image}', [ProductImageController::class, 'update']);
        Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy']);

        // Product Preview (token minting stays admin-only).
        Route::post('products/{product}/preview-token', [ProductPreviewController::class, 'generateToken']);
    });
