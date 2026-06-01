<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ProductBuilder API routes
|--------------------------------------------------------------------------
| Placeholder route confirming the module is wired up. Replace with the
| module's controllers as features are implemented.
*/

Route::get('product-builder/health', fn (): JsonResponse => response()->json([
    'module' => 'ProductBuilder',
    'status' => 'ok',
]));
