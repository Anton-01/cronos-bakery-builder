<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Factories;

use App\Modules\Catalog\Domain\Models\Product;
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
        $name = $this->faker->unique()->words(2, true) . ' cake';

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'price_amount' => $this->faker->numberBetween(1500, 12000),
            'currency' => 'USD',
            'is_active' => true,
        ];
    }
}
