<?php

declare(strict_types=1);

use App\Modules\Catalog\Presentation\Http\Controllers\Admin\AttributeController;
use App\Modules\Catalog\Presentation\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Modules\Catalog\Presentation\Http\Controllers\Admin\CollectionController as AdminCollectionController;
use App\Modules\Catalog\Presentation\Http\Controllers\Admin\ProductTaxonomyController;
use App\Modules\Catalog\Presentation\Http\Controllers\CatalogController;
use App\Modules\Catalog\Presentation\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catalog API routes
|--------------------------------------------------------------------------
| Loaded by CatalogServiceProvider under the "api" middleware + "/api" prefix.
*/

Route::prefix('catalog')->group(function (): void {
    // --- Simple product CRUD (Phase 1) ------------------------------------
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('products', [ProductController::class, 'store']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);
    });

    // --- Storefront browsing: dynamic filters, facets, SEO ----------------
    Route::get('browse', [CatalogController::class, 'index']);
    Route::get('facets', [CatalogController::class, 'facets']);
    Route::get('categories/{slug}', [CatalogController::class, 'category']);
    Route::get('detail/{slug}', [CatalogController::class, 'show']);
});

// --- Admin taxonomy management (admin guard + manage products) -------------
Route::prefix('admin/catalog')
    ->middleware(['auth:sanctum', 'admin', 'permission:manage products'])
    ->group(function (): void {
        Route::apiResource('categories', AdminCategoryController::class)
            ->parameters(['categories' => 'category'])->only(['index', 'store', 'update', 'destroy']);

        Route::apiResource('collections', AdminCollectionController::class)
            ->parameters(['collections' => 'collection'])->only(['index', 'store', 'update', 'destroy']);

        Route::apiResource('attributes', AttributeController::class)
            ->parameters(['attributes' => 'attribute'])->only(['index', 'store', 'update', 'destroy']);
        Route::post('attributes/{attribute}/values', [AttributeController::class, 'storeValue']);
        Route::delete('attributes/{attribute}/values/{value}', [AttributeController::class, 'destroyValue']);

        Route::put('products/{product}/taxonomy', [ProductTaxonomyController::class, 'update']);
    });
