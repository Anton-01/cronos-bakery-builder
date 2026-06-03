<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Infrastructure\Database\Factories;

use App\Modules\Notifications\Domain\Models\ReminderRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReminderRule>
 */
class ReminderRuleFactory extends Factory
{
    protected $model = ReminderRule::class;

    public function definition(): array
    {
        return [
            'offset_hours' => 24,
            'is_active' => true,
        ];
    }
}
