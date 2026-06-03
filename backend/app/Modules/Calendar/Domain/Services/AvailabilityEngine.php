<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Domain\Services;

use Illuminate\Support\Carbon;

/**
 * The scheduling engine. Given the earliest production-ready instant and the
 * calendar configuration, it computes the first valid date/slot and the set of
 * available slots over a window — honouring lead time, schedule, holidays,
 * blackouts and day/slot capacity.
 */
final class AvailabilityEngine
{
    /**
     * The first valid bookable slot at or after $earliest, or null if none
     * within the search window.
     *
     * @return array{date: string, slot_id: string, slot_label: string, at: string}|null
     */
    public function firstAvailable(Carbon $earliest, CalendarConfig $config, int $searchDays = 60): ?array
    {
        foreach ($this->range($earliest, $earliest->copy()->startOfDay(), $searchDays, $config) as $day) {
            if ($day['slots'] !== []) {
                $slot = $day['slots'][0];

                return [
                    'date' => $day['date'],
                    'slot_id' => $slot['id'],
                    'slot_label' => $slot['label'],
                    'at' => $day['date'] . 'T' . $slot['start_time'],
                ];
            }
        }

        return null;
    }

    /**
     * Available days (with their open slots and remaining capacity) across a
     * window starting at $from, never offering slots earlier than $earliest.
     *
     * @return array<int, array{date: string, weekday: int, slots: array<int, array<string, mixed>>}>
     */
    public function range(Carbon $earliest, Carbon $from, int $days, CalendarConfig $config): array
    {
        $result = [];
        $cursor = $from->copy()->startOfDay();

        for ($i = 0; $i < $days; $i++, $cursor->addDay()) {
            if (! $config->isDayOpen($cursor)) {
                continue;
            }

            $dayCapacityLeft = $config->dayCapacityLeft($cursor);
            if ($dayCapacityLeft === 0) {
                continue;
            }

            $slots = [];
            foreach ($config->slots() as $slot) {
                $slotAt = Carbon::parse($cursor->format('Y-m-d') . ' ' . $slot->start_time);

                // Respect the minimum production-ready instant.
                if ($slotAt->lessThan($earliest)) {
                    continue;
                }

                $remaining = $config->slotCapacityLeft($cursor, $slot);
                if ($remaining <= 0) {
                    continue;
                }

                $slots[] = [
                    'id' => $slot->id,
                    'label' => $slot->label,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'remaining' => $dayCapacityLeft === null
                        ? $remaining
                        : min($remaining, $dayCapacityLeft),
                ];
            }

            if ($slots !== []) {
                $result[] = [
                    'date' => $cursor->format('Y-m-d'),
                    'weekday' => $cursor->dayOfWeek,
                    'slots' => $slots,
                ];
            }
        }

        return $result;
    }
}
