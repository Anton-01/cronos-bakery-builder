<?php

declare(strict_types=1);

namespace Tests\Feature\CMS;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\CMS\Domain\Enums\BlockType;
use App\Modules\CMS\Domain\Enums\PageStatus;
use App\Modules\CMS\Domain\Enums\PageType;
use App\Modules\CMS\Domain\Models\Page;
use App\Modules\CMS\Domain\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAsCmsAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::Administrator->value); // has "manage cms"
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_an_authorised_admin_can_create_a_page(): void
    {
        $this->actingAsCmsAdmin();

        $response = $this->postJson('/api/admin/cms/pages', [
            'title' => 'Nosotros',
            'type' => PageType::About->value,
            'meta_title' => 'About Cronos',
            'meta_description' => 'Our story',
            'status' => PageStatus::Published->value,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'nosotros')
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('cms_pages', ['slug' => 'nosotros', 'status' => 'published']);
        $this->assertNotNull(Page::whereSlug('nosotros')->first()->published_at);
    }

    public function test_an_admin_without_the_cms_permission_is_forbidden(): void
    {
        $courier = Admin::factory()->create();
        $courier->assignRole(AdminRole::Courier->value); // no "manage cms"
        Sanctum::actingAs($courier);

        $this->postJson('/api/admin/cms/pages', [
            'title' => 'Nope',
            'type' => PageType::Landing->value,
            'status' => PageStatus::Draft->value,
        ])->assertForbidden();
    }

    public function test_guests_cannot_manage_pages(): void
    {
        $this->postJson('/api/admin/cms/pages', [])->assertUnauthorized();
    }

    public function test_an_admin_can_add_an_inline_block_to_a_page(): void
    {
        $this->actingAsCmsAdmin();
        $page = Page::factory()->create();

        $this->postJson("/api/admin/cms/pages/{$page->id}/blocks", [
            'type' => BlockType::Hero->value,
            'data' => ['heading' => 'Hello'],
        ])->assertCreated()->assertJsonPath('data.type', 'hero');

        $this->assertDatabaseHas('cms_page_sections', [
            'page_id' => $page->id,
            'type' => 'hero',
            'position' => 1,
        ]);
    }

    public function test_adding_a_block_from_a_reusable_section_inherits_its_type(): void
    {
        $this->actingAsCmsAdmin();
        $page = Page::factory()->create();
        $section = Section::factory()->ofType(BlockType::Testimonials)->create();

        $this->postJson("/api/admin/cms/pages/{$page->id}/blocks", [
            'section_id' => $section->id,
        ])->assertCreated()->assertJsonPath('data.type', 'testimonials');
    }

    public function test_an_admin_can_reorder_page_blocks(): void
    {
        $this->actingAsCmsAdmin();
        $page = Page::factory()->create();
        $first = $page->sections()->create(['type' => BlockType::Text->value, 'data' => [], 'position' => 0]);
        $second = $page->sections()->create(['type' => BlockType::Cta->value, 'data' => [], 'position' => 1]);

        $this->putJson("/api/admin/cms/pages/{$page->id}/blocks/reorder", [
            'order' => [$second->id, $first->id],
        ])->assertOk();

        $this->assertSame(0, $second->refresh()->position);
        $this->assertSame(1, $first->refresh()->position);
    }

    public function test_an_admin_can_manage_the_reusable_section_library(): void
    {
        $this->actingAsCmsAdmin();

        $create = $this->postJson('/api/admin/cms/sections', [
            'name' => 'Homepage hero',
            'type' => BlockType::Hero->value,
            'data' => ['heading' => 'Welcome'],
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->putJson("/api/admin/cms/sections/{$id}", [
            'name' => 'Homepage hero (v2)',
            'type' => BlockType::Hero->value,
            'data' => ['heading' => 'Welcome back'],
        ])->assertOk()->assertJsonPath('data.name', 'Homepage hero (v2)');

        $this->deleteJson("/api/admin/cms/sections/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('cms_sections', ['id' => $id]);
    }
}
