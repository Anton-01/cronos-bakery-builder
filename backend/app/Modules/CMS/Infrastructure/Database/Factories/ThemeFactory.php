<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Database\Factories;

use App\Modules\CMS\Domain\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    public function definition(): array
    {
        return [
            'name' => 'Default',
            'logo_path' => null,
            'favicon_path' => null,
            'colors' => [
                'primary' => '#b8693d',
                'secondary' => '#2c2420',
                'accent' => '#e0a458',
                'success' => '#1b7340',
                'warning' => '#c9920b',
                'danger' => '#b3261e',
            ],
            'fonts' => [
                'heading' => 'Playfair Display',
                'body' => 'Inter',
            ],
            'footer' => [
                'columns' => [],
                'copyright' => '© Cronos Bakery',
            ],
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }
}
