<?php

declare(strict_types=1);

namespace App\Modules\Orders\Infrastructure\Database\Factories;

use App\Modules\Orders\Domain\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'name' => 'Sucursal ' . $this->faker->unique()->city(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->numerify('+506########'),
            'is_active' => true,
            'position' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
