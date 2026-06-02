<?php

declare(strict_types=1);

use App\Modules\Notifications\Presentation\Http\Controllers\Admin\NotificationLogController;
use App\Modules\Notifications\Presentation\Http\Controllers\Admin\ReminderRuleController;
use App\Modules\Notifications\Presentation\Http\Controllers\Admin\TemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notifications (automation) API routes — admin configuration
|--------------------------------------------------------------------------
*/

Route::prefix('admin/notifications')
    ->middleware(['auth:sanctum', 'admin', 'permission:manage notifications'])
    ->group(function (): void {
        Route::apiResource('templates', TemplateController::class)
            ->parameters(['templates' => 'template'])->only(['index', 'store', 'update', 'destroy']);

        Route::apiResource('reminders', ReminderRuleController::class)
            ->parameters(['reminders' => 'rule'])->only(['index', 'store', 'update', 'destroy']);

        Route::get('logs', [NotificationLogController::class, 'index']);
    });
