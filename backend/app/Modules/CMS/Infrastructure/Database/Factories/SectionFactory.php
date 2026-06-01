<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Database\Factories;

use App\Modules\CMS\Domain\Enums\BlockType;
use App\Modules\CMS\Domain\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->words(2, true)),
            'type' => BlockType::Hero->value,
            'data' => [
                'heading' => $this->faker->sentence(3),
                'subheading' => $this->faker->sentence(),
                'cta_label' => 'Order now',
                'cta_url' => '/builder',
            ],
            'is_active' => true,
        ];
    }

    public function ofType(BlockType $type, array $data = []): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type->value,
            'data' => $data ?: $attributes['data'],
        ]);
    }
}
