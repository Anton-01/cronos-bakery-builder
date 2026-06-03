<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Database\Factories;

use App\Modules\Calendar\Domain\Models\Blackout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Blackout>
 */
class BlackoutFactory extends Factory
{
    protected $model = Blackout::class;

    public function definition(): array
    {
        return [
            'date' => now()->addDays(3)->toDateString(),
            'time_slot_id' => null,
            'reason' => 'Mantenimiento',
        ];
    }
}
