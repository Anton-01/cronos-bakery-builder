<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Infrastructure\Database\Factories;

use App\Modules\ProductBuilder\Domain\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->words(2, true)) . ' Cake';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'image' => null,
            'base_price_amount' => $this->faker->numberBetween(2000, 8000),
            'currency' => 'USD',
            'is_active' => true,
            'position' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
