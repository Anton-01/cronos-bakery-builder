<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Database\Factories;

use App\Modules\Calendar\Domain\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    protected $model = TimeSlot::class;

    public function definition(): array
    {
        return [
            'label' => '10:00 - 12:00',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'capacity' => 5,
            'is_active' => true,
            'position' => 0,
        ];
    }
}
