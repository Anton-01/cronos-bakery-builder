<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Factories;

use App\Modules\Catalog\Domain\Models\Attribute;
use App\Modules\Catalog\Domain\Models\AttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttributeValue>
 */
class AttributeValueFactory extends Factory
{
    protected $model = AttributeValue::class;

    public function definition(): array
    {
        $label = ucfirst($this->faker->unique()->word());

        return [
            'attribute_id' => Attribute::factory(),
            'label' => $label,
            'value' => Str::slug($label),
            'metadata' => null,
            'position' => 0,
        ];
    }
}
