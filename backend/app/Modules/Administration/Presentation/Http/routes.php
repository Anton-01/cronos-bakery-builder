<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Administration API routes
|--------------------------------------------------------------------------
| Placeholder route confirming the module is wired up. Replace with the
| module's controllers as features are implemented.
*/

Route::get('administration/health', fn (): JsonResponse => response()->json([
    'module' => 'Administration',
    'status' => 'ok',
]));
