<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Infrastructure\Database\Factories;

use App\Modules\ProductBuilder\Domain\Enums\OptionType;
use App\Modules\ProductBuilder\Domain\Models\Option;
use App\Modules\ProductBuilder\Domain\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Option>
 */
class OptionFactory extends Factory
{
    protected $model = Option::class;

    public function definition(): array
    {
        $label = ucfirst($this->faker->unique()->word());

        return [
            'product_id' => Product::factory(),
            'key' => Str::slug($label),
            'label' => $label,
            'type' => OptionType::Select->value,
            'help_text' => null,
            'is_required' => false,
            'position' => 0,
            'config' => null,
        ];
    }

    public function ofType(OptionType $type): static
    {
        return $this->state(fn (array $attributes) => ['type' => $type->value]);
    }

    public function required(): static
    {
        return $this->state(fn (array $attributes) => ['is_required' => true]);
    }
}
