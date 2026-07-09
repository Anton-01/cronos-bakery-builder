<?php

declare(strict_types=1);

use App\Modules\Calendar\Presentation\Http\Controllers\Admin\CalendarController as AdminCalendarController;
use App\Modules\Calendar\Presentation\Http\Controllers\AvailabilityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Calendar API routes
|--------------------------------------------------------------------------
*/

// Public availability lookup (delivery / pickup scheduling).
Route::get('calendar/availability', [AvailabilityController::class, 'index']);

// Admin configuration of the scheduling engine.
Route::prefix('admin/calendar')
    ->middleware(['auth:sanctum', 'admin', 'permission:manage calendar'])
    ->group(function (): void {
        Route::get('schedule', [AdminCalendarController::class, 'schedule']);
        Route::put('schedule', [AdminCalendarController::class, 'updateSchedule']);

        Route::get('slots', [AdminCalendarController::class, 'slots']);
        Route::post('slots', [AdminCalendarController::class, 'storeSlot']);
        Route::put('slots/{slot}', [AdminCalendarController::class, 'updateSlot'])->whereNumber('slot');
        Route::delete('slots/{slot}', [AdminCalendarController::class, 'destroySlot'])->whereNumber('slot');

        Route::get('holidays', [AdminCalendarController::class, 'holidays']);
        Route::post('holidays', [AdminCalendarController::class, 'storeHoliday']);
        Route::delete('holidays/{holiday}', [AdminCalendarController::class, 'destroyHoliday'])->whereNumber('holiday');

        Route::post('blackouts', [AdminCalendarController::class, 'storeBlackout']);
        Route::delete('blackouts/{blackout}', [AdminCalendarController::class, 'destroyBlackout'])->whereNumber('blackout');

        Route::put('production-rules', [AdminCalendarController::class, 'setProductionRule']);
    });
