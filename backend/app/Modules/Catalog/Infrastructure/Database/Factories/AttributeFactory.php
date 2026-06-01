<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Database\Factories;

use App\Modules\Catalog\Domain\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->word());

        return [
            'name' => $name,
            'code' => Str::slug($name),
            'type' => 'select',
            'is_filterable' => true,
            'position' => 0,
        ];
    }

    public function color(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'color']);
    }

    public function notFilterable(): static
    {
        return $this->state(fn (array $attributes) => ['is_filterable' => false]);
    }
}
