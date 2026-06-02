<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Database\Factories;

use App\Modules\Calendar\Domain\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return [
            'date' => now()->addDays(5)->toDateString(),
            'name' => 'Feriado',
            'is_recurring' => false,
        ];
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => ['is_recurring' => true]);
    }
}
