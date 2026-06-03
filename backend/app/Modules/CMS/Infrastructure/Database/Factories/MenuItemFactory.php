<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Database\Factories;

use App\Modules\CMS\Domain\Models\Menu;
use App\Modules\CMS\Domain\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'parent_id' => null,
            'label' => ucfirst($this->faker->word()),
            'url' => '/' . $this->faker->slug(1),
            'target' => '_self',
            'position' => 0,
            'is_active' => true,
        ];
    }
}
