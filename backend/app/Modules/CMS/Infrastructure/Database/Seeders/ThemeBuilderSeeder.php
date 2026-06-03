<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Database\Seeders;

use App\Modules\CMS\Domain\Enums\BannerPlacement;
use App\Modules\CMS\Domain\Enums\MenuLocation;
use App\Modules\CMS\Domain\Models\Banner;
use App\Modules\CMS\Domain\Models\Menu;
use App\Modules\CMS\Domain\Models\Theme;
use Illuminate\Database\Seeder;

/**
 * Seeds the default active theme, the storefront navigation menu (with nested
 * items) and a couple of sample banners.
 */
class ThemeBuilderSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTheme();
        $this->seedHeaderMenu();
        $this->seedBanners();
    }

    private function seedTheme(): void
    {
        Theme::factory()->active()->create([
            'name' => 'Cronos Default',
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
                'columns' => [
                    ['title' => 'Cronos Bakery', 'links' => [
                        ['label' => 'Nosotros', 'url' => '/p/about'],
                        ['label' => 'Contacto', 'url' => '/p/contact'],
                    ]],
                    ['title' => 'Ayuda', 'links' => [
                        ['label' => 'FAQ', 'url' => '/p/faq'],
                        ['label' => 'Políticas', 'url' => '/p/policies'],
                    ]],
                ],
                'copyright' => '© ' . date('Y') . ' Cronos Bakery. Todos los derechos reservados.',
            ],
        ]);
    }

    private function seedHeaderMenu(): void
    {
        $menu = Menu::factory()->location(MenuLocation::Header)->create([
            'name' => 'Navegación principal',
        ]);

        $position = 0;
        $menu->items()->create(['label' => 'Inicio', 'url' => '/', 'position' => $position++]);
        $pasteles = $menu->items()->create(['label' => 'Pasteles', 'url' => '/catalog', 'position' => $position++]);
        $menu->items()->create(['label' => 'Blog', 'url' => '/p/blog', 'position' => $position++]);
        $menu->items()->create(['label' => 'Contacto', 'url' => '/p/contact', 'position' => $position++]);

        // Nested children under "Pasteles".
        $childPosition = 0;
        foreach (['Floral' => '/catalog?style=floral', 'Moderno' => '/catalog?style=modern', 'Mini Cakes' => '/catalog?style=mini'] as $label => $url) {
            $menu->items()->create([
                'parent_id' => $pasteles->id,
                'label' => $label,
                'url' => $url,
                'position' => $childPosition++,
            ]);
        }
    }

    private function seedBanners(): void
    {
        Banner::factory()->placement(BannerPlacement::HomeTop)->create([
            'title' => 'Pasteles de temporada',
            'image_path' => '/images/banners/seasonal.jpg',
            'link_url' => '/catalog',
            'sort_order' => 0,
        ]);

        Banner::factory()->placement(BannerPlacement::HomeMiddle)->create([
            'title' => 'Arma tu pastel',
            'image_path' => '/images/banners/builder.jpg',
            'link_url' => '/builder',
            'sort_order' => 0,
        ]);
    }
}
