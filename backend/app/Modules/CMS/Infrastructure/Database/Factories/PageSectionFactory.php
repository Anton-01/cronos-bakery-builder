<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Database\Factories;

use App\Modules\CMS\Domain\Enums\BlockType;
use App\Modules\CMS\Domain\Models\Page;
use App\Modules\CMS\Domain\Models\PageSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageSection>
 */
class PageSectionFactory extends Factory
{
    protected $model = PageSection::class;

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
}
