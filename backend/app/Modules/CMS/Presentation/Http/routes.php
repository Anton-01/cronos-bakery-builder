<?php

declare(strict_types=1);

use App\Modules\CMS\Presentation\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Modules\CMS\Presentation\Http\Controllers\Admin\BrandController;
use App\Modules\CMS\Presentation\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Modules\CMS\Presentation\Http\Controllers\Admin\MenuItemController;
use App\Modules\CMS\Presentation\Http\Controllers\Admin\PageBlockController;
use App\Modules\CMS\Presentation\Http\Controllers\Admin\PageController;
use App\Modules\CMS\Presentation\Http\Controllers\Admin\SectionController;
use App\Modules\CMS\Presentation\Http\Controllers\Admin\ThemeController as AdminThemeController;
use App\Modules\CMS\Presentation\Http\Controllers\BannerController;
use App\Modules\CMS\Presentation\Http\Controllers\MenuController;
use App\Modules\CMS\Presentation\Http\Controllers\PublicPageController;
use App\Modules\CMS\Presentation\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CMS API routes
|--------------------------------------------------------------------------
*/

// --- Public (consumed by the Vue frontend) --------------------------------
Route::prefix('cms')->group(function (): void {
    Route::get('pages', [PublicPageController::class, 'index']);
    Route::get('pages/{slug}', [PublicPageController::class, 'show']);
});

// Theme Builder — active theme, menus and banners for dynamic rendering.
Route::get('theme', [ThemeController::class, 'show']);
Route::get('menus/{location}', [MenuController::class, 'show']);
Route::get('banners/{placement}', [BannerController::class, 'index']);

// --- Admin: CMS pages & sections (manage cms) ------------------------------
Route::prefix('admin/cms')
    ->middleware(['auth:sanctum', 'admin', 'permission:manage cms'])
    ->group(function (): void {
        // Brands (tenants) the admin can manage content for.
        Route::get('brands', [BrandController::class, 'index']);

        // Reusable section library.
        Route::apiResource('sections', SectionController::class)->parameters(['sections' => 'section']);

        // Pages.
        Route::apiResource('pages', PageController::class)->parameters(['pages' => 'page']);
        Route::put('pages/{page}/publish', [PageController::class, 'publish']);
        Route::put('pages/{page}/unpublish', [PageController::class, 'unpublish']);

        // Page-builder blocks.
        Route::post('pages/{page}/blocks', [PageBlockController::class, 'store']);
        Route::put('pages/{page}/blocks/sync', [PageBlockController::class, 'sync']);
        Route::put('pages/{page}/blocks/reorder', [PageBlockController::class, 'reorder']);
        Route::put('pages/{page}/blocks/{block}', [PageBlockController::class, 'update']);
        Route::delete('pages/{page}/blocks/{block}', [PageBlockController::class, 'destroy']);
    });

// --- Admin: Theme Builder (manage theme) -----------------------------------
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin', 'permission:manage theme'])
    ->group(function (): void {
        // Themes (branding).
        Route::get('themes', [AdminThemeController::class, 'index']);
        Route::post('themes', [AdminThemeController::class, 'store']);
        Route::put('themes/{theme}', [AdminThemeController::class, 'update']);
        Route::put('themes/{theme}/activate', [AdminThemeController::class, 'activate']);

        // Menus + nested items.
        Route::apiResource('menus', AdminMenuController::class)->parameters(['menus' => 'menu']);
        Route::post('menus/{menu}/items', [MenuItemController::class, 'store']);
        Route::put('menus/{menu}/items/{item}', [MenuItemController::class, 'update']);
        Route::delete('menus/{menu}/items/{item}', [MenuItemController::class, 'destroy']);

        // Banners.
        Route::apiResource('banners', AdminBannerController::class)->parameters(['banners' => 'banner']);
    });
