<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Infrastructure\Database\Factories;

use App\Modules\Calendar\Domain\Models\ProductionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionRule>
 */
class ProductionRuleFactory extends Factory
{
    protected $model = ProductionRule::class;

    public function definition(): array
    {
        return [
            'product_id' => null,
            'lead_time_hours' => 48,
        ];
    }
}
