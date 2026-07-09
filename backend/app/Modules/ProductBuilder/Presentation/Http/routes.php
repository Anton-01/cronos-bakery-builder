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
        Route::apiResource('products', ProductController::class)
            ->parameters(['products' => 'product'])
            ->whereNumber('product');

        // Options.
        Route::post('products/{product}/options', [OptionController::class, 'store'])->whereNumber('product');
        Route::put('products/{product}/options/{option}', [OptionController::class, 'update'])->whereNumber(['product', 'option']);
        Route::delete('products/{product}/options/{option}', [OptionController::class, 'destroy'])->whereNumber(['product', 'option']);

        // Option values.
        Route::post('products/{product}/options/{option}/values', [OptionValueController::class, 'store'])->whereNumber(['product', 'option']);
        Route::put('products/{product}/options/{option}/values/{value}', [OptionValueController::class, 'update'])->whereNumber(['product', 'option', 'value']);
        Route::delete('products/{product}/options/{option}/values/{value}', [OptionValueController::class, 'destroy'])->whereNumber(['product', 'option', 'value']);

        // Conditional rules.
        Route::post('products/{product}/rules', [OptionRuleController::class, 'store'])->whereNumber('product');
        Route::delete('products/{product}/rules/{rule}', [OptionRuleController::class, 'destroy'])->whereNumber(['product', 'rule']);

        // Option Templates (global, independent of products).
        Route::get('option-templates', [OptionTemplateController::class, 'index']);
        Route::post('option-templates', [OptionTemplateController::class, 'store']);
        Route::put('option-templates/{template}', [OptionTemplateController::class, 'update'])->whereNumber('template');
        Route::delete('option-templates/{template}', [OptionTemplateController::class, 'destroy'])->whereNumber('template');

        // Option Template Values.
        Route::post('option-templates/{template}/values', [OptionTemplateValueController::class, 'store'])->whereNumber('template');
        Route::put('option-templates/{template}/values/{value}', [OptionTemplateValueController::class, 'update'])->whereNumber(['template', 'value']);
        Route::delete('option-templates/{template}/values/{value}', [OptionTemplateValueController::class, 'destroy'])->whereNumber(['template', 'value']);

        // Product-Option Links.
        Route::get('products/{product}/option-links', [ProductOptionLinkController::class, 'index'])->whereNumber('product');
        Route::post('products/{product}/option-links', [ProductOptionLinkController::class, 'store'])->whereNumber('product');
        Route::put('products/{product}/option-links/{link}', [ProductOptionLinkController::class, 'update'])->whereNumber(['product', 'link']);
        Route::delete('products/{product}/option-links/{link}', [ProductOptionLinkController::class, 'destroy'])->whereNumber(['product', 'link']);


        // Product Images.
        Route::post('products/{product}/images', [ProductImageController::class, 'store'])->whereNumber('product');
        Route::put('products/{product}/images/{image}', [ProductImageController::class, 'update'])->whereNumber(['product', 'image']);
        Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->whereNumber(['product', 'image']);

        // Product Preview (token minting stays admin-only).
        Route::post('products/{product}/preview-token', [ProductPreviewController::class, 'generateToken'])->whereNumber('product');
    });
