<?php

declare(strict_types=1);

namespace Tests\Feature\CMS;

use App\Modules\CMS\Domain\Enums\BannerPlacement;
use App\Modules\CMS\Domain\Enums\MenuLocation;
use App\Modules\CMS\Domain\Models\Banner;
use App\Modules\CMS\Domain\Models\Menu;
use App\Modules\CMS\Domain\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_active_theme_is_exposed_publicly(): void
    {
        Theme::factory()->create(['name' => 'Old', 'is_active' => false]);
        Theme::factory()->active()->create(['name' => 'Current']);

        $this->getJson('/api/theme')
            ->assertOk()
            ->assertJsonPath('data.name', 'Current')
            ->assertJsonPath('data.colors.primary', '#b8693d')
            ->assertJsonPath('data.fonts.heading', 'Playfair Display');
    }

    public function test_a_menu_is_returned_as_a_nested_tree_by_location(): void
    {
        $menu = Menu::factory()->location(MenuLocation::Header)->create();
        $pasteles = $menu->items()->create(['label' => 'Pasteles', 'url' => '/catalog', 'position' => 0]);
        $menu->items()->create(['parent_id' => $pasteles->id, 'label' => 'Floral', 'url' => '/x', 'position' => 0]);
        $menu->items()->create(['parent_id' => $pasteles->id, 'label' => 'Moderno', 'url' => '/y', 'position' => 1]);

        $this->getJson('/api/menus/header')
            ->assertOk()
            ->assertJsonPath('data.location', 'header')
            ->assertJsonPath('data.items.0.label', 'Pasteles')
            ->assertJsonCount(2, 'data.items.0.children')
            ->assertJsonPath('data.items.0.children.0.label', 'Floral');
    }

    public function test_unknown_menu_location_returns_404(): void
    {
        $this->getJson('/api/menus/sidebar')->assertNotFound();
    }

    public function test_only_live_banners_for_a_placement_are_returned(): void
    {
        Banner::factory()->placement(BannerPlacement::HomeTop)->create(['title' => 'Live']);
        Banner::factory()->placement(BannerPlacement::HomeTop)->expired()->create(['title' => 'Expired']);
        Banner::factory()->placement(BannerPlacement::HomeMiddle)->create(['title' => 'Other placement']);

        $response = $this->getJson('/api/banners/home_top')->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('Live', $response->json('data.0.title'));
    }
}
