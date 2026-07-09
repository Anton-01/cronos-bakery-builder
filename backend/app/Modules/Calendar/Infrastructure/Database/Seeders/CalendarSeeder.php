<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Database\Seeders;

use App\Modules\Calendar\Domain\Enums\LeadTime;
use App\Modules\Calendar\Domain\Models\Holiday;
use App\Modules\Calendar\Domain\Models\ProductionRule;
use App\Modules\Calendar\Domain\Models\ScheduleDay;
use App\Modules\Calendar\Domain\Models\TimeSlot;
use App\Modules\ProductBuilder\Domain\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Seeds a sensible default scheduling configuration: open Monday–Saturday,
 * three daily slots, a default 48h production rule (72h for the Signature Cake)
 * and an upcoming holiday.
 */
class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        // Open Mon–Sat (weekday 1..6), closed Sunday (0). 0 capacity = unlimited.
        for ($weekday = 0; $weekday <= 6; $weekday++) {
            ScheduleDay::query()->updateOrCreate(
                ['weekday' => $weekday],
                ['is_open' => $weekday !== 0, 'capacity' => 20],
            );
        }

        $slots = [
            ['10:00 - 12:00', '10:00', '12:00'],
            ['12:00 - 14:00', '12:00', '14:00'],
            ['16:00 - 18:00', '16:00', '18:00'],
        ];
        foreach ($slots as $position => [$label, $start, $end]) {
            // Idempotente: re-ejecutar --seed jamás duplica slots.
            TimeSlot::query()->updateOrCreate(
                ['label' => $label],
                [
                    'start_time' => $start, 'end_time' => $end,
                    'capacity' => 8, 'position' => $position, 'is_active' => true,
                ],
            );
        }

        // Default production rule + a slower one for the Signature Cake.
        ProductionRule::query()->updateOrCreate(['product_id' => null], ['lead_time_hours' => LeadTime::TwoDays->value]);

        $signatureId = Product::query()->where('slug', 'signature-cake')->value('id');
        if ($signatureId !== null) {
            ProductionRule::query()->updateOrCreate(
                ['product_id' => $signatureId],
                ['lead_time_hours' => LeadTime::ThreeDays->value],
            );
        }

        Holiday::query()->updateOrCreate(
            ['name' => 'Día festivo'],
            ['date' => now()->addDays(14)->toDateString(), 'is_recurring' => false],
        );
    }
}
