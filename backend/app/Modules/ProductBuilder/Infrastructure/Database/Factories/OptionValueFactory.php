<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Infrastructure\Database\Factories;

use App\Modules\ProductBuilder\Domain\Enums\PriceModifierType;
use App\Modules\ProductBuilder\Domain\Models\Option;
use App\Modules\ProductBuilder\Domain\Models\OptionValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OptionValue>
 */
class OptionValueFactory extends Factory
{
    protected $model = OptionValue::class;

    public function definition(): array
    {
        $label = ucfirst($this->faker->unique()->word());

        return [
            'option_id' => Option::factory(),
            'label' => $label,
            'value' => Str::slug($label),
            'price_modifier_type' => PriceModifierType::None->value,
            'price_modifier_amount' => 0,
            'metadata' => null,
            'is_default' => false,
            'position' => 0,
        ];
    }

    public function priced(PriceModifierType $type, int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'price_modifier_type' => $type->value,
            'price_modifier_amount' => $amount,
        ]);
    }
}
