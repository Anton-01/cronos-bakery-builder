<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Database\Factories;

use App\Modules\CMS\Domain\Enums\MenuLocation;
use App\Modules\CMS\Domain\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        return [
            'name' => 'Main navigation',
            'location' => MenuLocation::Header->value,
            'is_active' => true,
        ];
    }

    public function location(MenuLocation $location): static
    {
        return $this->state(fn (array $attributes) => ['location' => $location->value]);
    }
}
