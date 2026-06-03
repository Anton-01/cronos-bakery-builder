<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Database\Factories;

use App\Modules\CMS\Domain\Enums\BannerPlacement;
use App\Modules\CMS\Domain\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'title' => ucfirst($this->faker->words(3, true)),
            'image_path' => '/images/banners/' . $this->faker->slug(2) . '.jpg',
            'link_url' => '/catalog',
            'placement' => BannerPlacement::HomeTop->value,
            'sort_order' => 0,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function placement(BannerPlacement $placement): static
    {
        return $this->state(fn (array $attributes) => ['placement' => $placement->value]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
        ]);
    }
}
