<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Application\Services;

use App\Modules\Calendar\Domain\Models\Blackout;
use App\Modules\Calendar\Domain\Models\Holiday;
use App\Modules\Calendar\Domain\Models\ProductionRule;
use App\Modules\Calendar\Domain\Models\ScheduleDay;
use App\Modules\Calendar\Domain\Models\TimeSlot;
use Illuminate\Support\Collection;

/**
 * Administrative configuration of the scheduling engine: weekly schedule, time
 * slots, holidays, blackouts and per-product production rules.
 */
final class CalendarAdminService
{
    /**
     * Upsert the weekly schedule.
     *
     * @param  array<int, array{weekday:int, is_open:bool, capacity:int}>  $days
     * @return Collection<int, ScheduleDay>
     */
    public function updateSchedule(array $days): Collection
    {
        foreach ($days as $day) {
            ScheduleDay::query()->updateOrCreate(
                ['weekday' => $day['weekday']],
                ['is_open' => $day['is_open'] ?? true, 'capacity' => $day['capacity'] ?? 0],
            );
        }

        return ScheduleDay::query()->orderBy('weekday')->get();
    }

    // --- Time slots ---------------------------------------------------------

    /** @param array<string, mixed> $attributes */
    public function createSlot(array $attributes): TimeSlot
    {
        return TimeSlot::create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function updateSlot(string $id, array $attributes): TimeSlot
    {
        $slot = TimeSlot::query()->findOrFail($id);
        $slot->update($attributes);

        return $slot->refresh();
    }

    public function deleteSlot(string $id): void
    {
        TimeSlot::query()->findOrFail($id)->delete();
    }

    // --- Holidays -----------------------------------------------------------

    /** @param array<string, mixed> $attributes */
    public function createHoliday(array $attributes): Holiday
    {
        return Holiday::create($attributes);
    }

    public function deleteHoliday(string $id): void
    {
        Holiday::query()->findOrFail($id)->delete();
    }

    // --- Blackouts ----------------------------------------------------------

    /** @param array<string, mixed> $attributes */
    public function createBlackout(array $attributes): Blackout
    {
        return Blackout::create($attributes);
    }

    public function deleteBlackout(string $id): void
    {
        Blackout::query()->findOrFail($id)->delete();
    }

    // --- Production rules ----------------------------------------------------

    public function setProductionRule(?string $productId, int $leadTimeHours): ProductionRule
    {
        return ProductionRule::query()->updateOrCreate(
            ['product_id' => $productId],
            ['lead_time_hours' => $leadTimeHours],
        );
    }
}
