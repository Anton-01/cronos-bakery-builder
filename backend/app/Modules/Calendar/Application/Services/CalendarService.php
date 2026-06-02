<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Application\Services;

use App\Modules\Calendar\Domain\Models\Blackout;
use App\Modules\Calendar\Domain\Models\Booking;
use App\Modules\Calendar\Domain\Models\Holiday;
use App\Modules\Calendar\Domain\Models\ProductionRule;
use App\Modules\Calendar\Domain\Models\ScheduleDay;
use App\Modules\Calendar\Domain\Models\TimeSlot;
use App\Modules\Calendar\Domain\Services\AvailabilityEngine;
use App\Modules\Calendar\Domain\Services\CalendarConfig;
use App\Modules\ProductBuilder\Domain\Models\Product;
use Illuminate\Support\Carbon;

/**
 * Loads the calendar configuration and drives the {@see AvailabilityEngine} to
 * answer availability questions for a product, and to reserve capacity.
 */
final class CalendarService
{
    private const DEFAULT_LEAD_HOURS = 48;

    public function __construct(private readonly AvailabilityEngine $engine)
    {
    }

    /**
     * Production lead time (hours) for a product, falling back to the global
     * default rule and finally the hard default.
     */
    public function leadTimeHours(?string $productId): int
    {
        $rule = $productId !== null
            ? ProductionRule::query()->where('product_id', $productId)->first()
            : null;

        $rule ??= ProductionRule::query()->whereNull('product_id')->first();

        return $rule?->lead_time_hours ?? self::DEFAULT_LEAD_HOURS;
    }

    public function earliestFor(?string $productId, ?Carbon $now = null): Carbon
    {
        return ($now ?? Carbon::now())->copy()->addHours($this->leadTimeHours($productId));
    }

    public function resolveProductId(string $slug): ?string
    {
        return Product::query()->where('slug', $slug)->value('id');
    }

    /**
     * Available days/slots for a product over a window.
     *
     * @return array<int, array<string, mixed>>
     */
    public function availability(?string $productId, ?Carbon $from = null, int $days = 30): array
    {
        $earliest = $this->earliestFor($productId);
        $from = $from ?? $earliest;
        $config = $this->buildConfig();

        return $this->engine->range($earliest, $from, $days, $config);
    }

    /**
     * The first valid date/slot for a product.
     *
     * @return array<string, string>|null
     */
    public function minimumDate(?string $productId): ?array
    {
        return $this->engine->firstAvailable($this->earliestFor($productId), $this->buildConfig());
    }

    public function reserve(Carbon $date, ?string $slotId, int $quantity = 1, ?string $reference = null): Booking
    {
        return Booking::create([
            'date' => $date->toDateString(),
            'time_slot_id' => $slotId,
            'quantity' => $quantity,
            'reference' => $reference,
        ]);
    }

    public function buildConfig(): CalendarConfig
    {
        $schedule = ScheduleDay::query()->get()->keyBy('weekday')->all();
        $slots = TimeSlot::query()->active()->orderBy('position')->orderBy('start_time')->get();

        $holidayDates = [];
        $holidayRecurring = [];
        foreach (Holiday::query()->get() as $holiday) {
            if ($holiday->is_recurring) {
                $holidayRecurring[$holiday->date->format('m-d')] = true;
            } else {
                $holidayDates[$holiday->date->format('Y-m-d')] = true;
            }
        }

        $blackoutDays = [];
        $blackoutSlots = [];
        foreach (Blackout::query()->get() as $blackout) {
            $date = $blackout->date->format('Y-m-d');
            if ($blackout->time_slot_id === null) {
                $blackoutDays[$date] = true;
            } else {
                $blackoutSlots[$date . '|' . $blackout->time_slot_id] = true;
            }
        }

        [$dayBookings, $slotBookings] = $this->bookingCounts();

        return new CalendarConfig(
            scheduleByWeekday: $schedule,
            slots: $slots,
            holidayDates: $holidayDates,
            holidayRecurring: $holidayRecurring,
            blackoutDays: $blackoutDays,
            blackoutSlots: $blackoutSlots,
            dayBookings: $dayBookings,
            slotBookings: $slotBookings,
        );
    }

    /**
     * @return array{0: array<string, int>, 1: array<string, int>}
     */
    private function bookingCounts(): array
    {
        $dayBookings = [];
        $slotBookings = [];

        foreach (Booking::query()->where('date', '>=', Carbon::now()->toDateString())->get() as $booking) {
            $date = $booking->date->format('Y-m-d');
            $dayBookings[$date] = ($dayBookings[$date] ?? 0) + $booking->quantity;

            if ($booking->time_slot_id !== null) {
                $key = $date . '|' . $booking->time_slot_id;
                $slotBookings[$key] = ($slotBookings[$key] ?? 0) + $booking->quantity;
            }
        }

        return [$dayBookings, $slotBookings];
    }
}
