<?php

declare(strict_types=1);

use App\Modules\CMS\Presentation\Http\Controllers\Admin\PageBlockController;
use App\Modules\CMS\Presentation\Http\Controllers\Admin\PageController;
use App\Modules\CMS\Presentation\Http\Controllers\Admin\SectionController;
use App\Modules\CMS\Presentation\Http\Controllers\PublicPageController;
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

// --- Admin management (independent admin guard + granular permission) ------
Route::prefix('admin/cms')
    ->middleware(['auth:sanctum', 'admin', 'permission:manage cms'])
    ->group(function (): void {
        // Reusable section library.
        Route::apiResource('sections', SectionController::class)->parameters(['sections' => 'section']);

        // Pages.
        Route::apiResource('pages', PageController::class)->parameters(['pages' => 'page']);

        // Page-builder blocks.
        Route::post('pages/{page}/blocks', [PageBlockController::class, 'store']);
        Route::put('pages/{page}/blocks/reorder', [PageBlockController::class, 'reorder']);
        Route::put('pages/{page}/blocks/{block}', [PageBlockController::class, 'update']);
        Route::delete('pages/{page}/blocks/{block}', [PageBlockController::class, 'destroy']);
    });
