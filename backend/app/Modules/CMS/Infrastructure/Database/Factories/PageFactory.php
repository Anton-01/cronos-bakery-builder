<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Database\Factories;

use App\Modules\CMS\Domain\Enums\PageStatus;
use App\Modules\CMS\Domain\Enums\PageType;
use App\Modules\CMS\Domain\Models\Brand;
use App\Modules\CMS\Domain\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = ucfirst($this->faker->unique()->words(2, true));

        return [
            'brand_id' => Brand::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'type' => PageType::Landing->value,
            'meta_title' => $title,
            'meta_description' => $this->faker->sentence(),
            'content' => null,
            'settings' => null,
            'status' => PageStatus::Draft->value,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PageStatus::Published->value,
            'published_at' => now(),
        ]);
    }

    public function ofType(PageType $type): static
    {
        return $this->state(fn (array $attributes) => ['type' => $type->value]);
    }

    public function forBrand(Brand $brand): static
    {
        return $this->state(fn (array $attributes) => ['brand_id' => $brand->id]);
    }
}
