<?php

declare(strict_types=1);

namespace Tests\Feature\CMS;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\CMS\Domain\Enums\MenuLocation;
use App\Modules\CMS\Domain\Models\Menu;
use App\Modules\CMS\Domain\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminThemeBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAsThemeAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::Administrator->value); // has "manage theme"
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function validThemePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Brand A',
            'colors' => [
                'primary' => '#112233', 'secondary' => '#222222', 'accent' => '#333333',
                'success' => '#1b7340', 'warning' => '#c9920b', 'danger' => '#b3261e',
            ],
            'fonts' => ['heading' => 'Lora', 'body' => 'Inter'],
            'is_active' => true,
        ], $overrides);
    }

    public function test_an_admin_can_create_and_activate_a_theme(): void
    {
        $this->actingAsThemeAdmin();

        $this->postJson('/api/admin/themes', $this->validThemePayload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Brand A')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('themes', ['name' => 'Brand A', 'is_active' => true]);
    }

    public function test_activating_a_theme_deactivates_the_others(): void
    {
        $this->actingAsThemeAdmin();
        $current = Theme::factory()->active()->create();
        $other = Theme::factory()->create();

        $this->putJson("/api/admin/themes/{$other->id}/activate")->assertOk();

        $this->assertFalse($current->refresh()->is_active);
        $this->assertTrue($other->refresh()->is_active);
    }

    public function test_theme_colors_must_be_valid_hex(): void
    {
        $this->actingAsThemeAdmin();

        $this->postJson('/api/admin/themes', $this->validThemePayload([
            'colors' => ['primary' => 'not-a-color'] + $this->validThemePayload()['colors'],
        ]))->assertStatus(422)->assertJsonValidationErrors('colors.primary');
    }

    public function test_an_admin_can_build_a_nested_menu(): void
    {
        $this->actingAsThemeAdmin();
        $menu = Menu::factory()->location(MenuLocation::Header)->create();

        $parent = $this->postJson("/api/admin/menus/{$menu->id}/items", [
            'label' => 'Pasteles', 'url' => '/catalog',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/admin/menus/{$menu->id}/items", [
            'label' => 'Floral', 'url' => '/catalog?style=floral', 'parent_id' => $parent,
        ])->assertCreated()->assertJsonPath('data.parent_id', $parent);

        $this->assertDatabaseHas('menu_items', ['label' => 'Floral', 'parent_id' => $parent]);
    }

    public function test_an_admin_can_manage_banners(): void
    {
        $this->actingAsThemeAdmin();

        $id = $this->postJson('/api/admin/banners', [
            'title' => 'Promo',
            'image_path' => '/img/promo.jpg',
            'placement' => 'home_top',
        ])->assertCreated()->json('data.id');

        $this->deleteJson("/api/admin/banners/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('banners', ['id' => $id]);
    }

    public function test_an_admin_without_manage_theme_permission_is_forbidden(): void
    {
        $courier = Admin::factory()->create();
        $courier->assignRole(AdminRole::Courier->value);
        Sanctum::actingAs($courier);

        $this->postJson('/api/admin/themes', $this->validThemePayload())->assertForbidden();
    }

    public function test_guests_cannot_manage_the_theme(): void
    {
        $this->postJson('/api/admin/themes', [])->assertUnauthorized();
    }
}
