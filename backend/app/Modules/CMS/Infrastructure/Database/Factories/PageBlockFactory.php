<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Database\Factories;

use App\Modules\CMS\Domain\Enums\BlockType;
use App\Modules\CMS\Domain\Models\Page;
use App\Modules\CMS\Domain\Models\PageBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageBlock>
 */
class PageBlockFactory extends Factory
{
    protected $model = PageBlock::class;

    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'section_id' => null,
            'type' => BlockType::Text->value,
            'data' => ['body' => $this->faker->paragraph()],
            'position' => 0,
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
