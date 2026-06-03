<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Database\Factories;

use App\Modules\Calendar\Domain\Models\ScheduleDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleDay>
 */
class ScheduleDayFactory extends Factory
{
    protected $model = ScheduleDay::class;

    public function definition(): array
    {
        return [
            'weekday' => 1,
            'is_open' => true,
            'capacity' => 0,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['is_open' => false]);
    }
}
