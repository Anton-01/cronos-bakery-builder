<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Services;

use App\Modules\Calendar\Domain\Models\ScheduleDay;
use App\Modules\Calendar\Domain\Models\TimeSlot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Immutable snapshot of the calendar configuration the engine reasons over:
 * weekly schedule, bookable slots, holidays, blackouts and current bookings.
 */
final class CalendarConfig
{
    /**
     * @param  array<int, ScheduleDay>  $scheduleByWeekday
     * @param  Collection<int, TimeSlot>  $slots  active, ordered by start time
     * @param  array<string, true>  $holidayDates  'Y-m-d' => true
     * @param  array<string, true>  $holidayRecurring  'm-d' => true
     * @param  array<string, true>  $blackoutDays  'Y-m-d' => true
     * @param  array<string, true>  $blackoutSlots  'Y-m-d|slotId' => true
     * @param  array<string, int>  $dayBookings  'Y-m-d' => count
     * @param  array<string, int>  $slotBookings  'Y-m-d|slotId' => count
     */
    public function __construct(
        private readonly array $scheduleByWeekday,
        private readonly Collection $slots,
        private readonly array $holidayDates,
        private readonly array $holidayRecurring,
        private readonly array $blackoutDays,
        private readonly array $blackoutSlots,
        private readonly array $dayBookings,
        private readonly array $slotBookings,
    ) {
    }

    /**
     * @return Collection<int, TimeSlot>
     */
    public function slots(): Collection
    {
        return $this->slots;
    }

    public function isHoliday(Carbon $date): bool
    {
        return isset($this->holidayDates[$date->format('Y-m-d')])
            || isset($this->holidayRecurring[$date->format('m-d')]);
    }

    /**
     * Whether the weekday is configured open and the date is neither a holiday
     * nor a full-day blackout.
     */
    public function isDayOpen(Carbon $date): bool
    {
        $schedule = $this->scheduleByWeekday[$date->dayOfWeek] ?? null;

        if ($schedule === null || ! $schedule->is_open) {
            return false;
        }

        if ($this->isHoliday($date)) {
            return false;
        }

        return ! isset($this->blackoutDays[$date->format('Y-m-d')]);
    }

    /**
     * Remaining capacity for the whole day, or null when unlimited.
     */
    public function dayCapacityLeft(Carbon $date): ?int
    {
        $schedule = $this->scheduleByWeekday[$date->dayOfWeek] ?? null;
        $capacity = (int) ($schedule?->capacity ?? 0);

        if ($capacity === 0) {
            return null; // unlimited
        }

        return max(0, $capacity - ($this->dayBookings[$date->format('Y-m-d')] ?? 0));
    }

    /**
     * Remaining capacity for a specific slot on a date (0 if blacked out).
     */
    public function slotCapacityLeft(Carbon $date, TimeSlot $slot): int
    {
        $key = $date->format('Y-m-d') . '|' . $slot->id;

        if (isset($this->blackoutSlots[$key])) {
            return 0;
        }

        return max(0, $slot->capacity - ($this->slotBookings[$key] ?? 0));
    }
}
