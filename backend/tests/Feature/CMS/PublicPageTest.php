<?php

declare(strict_types=1);

namespace Tests\Feature\CMS;

use App\Modules\CMS\Domain\Enums\BlockType;
use App\Modules\CMS\Domain\Models\Brand;
use App\Modules\CMS\Domain\Models\Page;
use App\Modules\CMS\Domain\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_page_is_returned_by_slug_with_resolved_blocks(): void
    {
        $page = Page::factory()->published()->create(['slug' => 'home']);
        $page->blocks()->create([
            'type' => BlockType::Hero->value,
            'data' => ['heading' => 'Welcome'],
            'position' => 0,
        ]);

        $this->getJson('/api/cms/pages/home')
            ->assertOk()
            ->assertJsonPath('data.slug', 'home')
            ->assertJsonPath('data.seo.meta_title', $page->meta_title)
            ->assertJsonPath('data.blocks.0.type', 'hero')
            ->assertJsonPath('data.blocks.0.config.heading', 'Welcome');
    }

    public function test_a_referenced_reusable_section_resolves_its_data(): void
    {
        $section = Section::factory()->ofType(BlockType::Banner, ['text' => 'Reusable banner'])->create();
        $page = Page::factory()->published()->create(['slug' => 'promo']);
        $page->blocks()->create([
            'section_id' => $section->id,
            'type' => BlockType::Banner->value,
            'data' => null,
            'position' => 0,
        ]);

        $this->getJson('/api/cms/pages/promo')
            ->assertOk()
            ->assertJsonPath('data.blocks.0.type', 'banner')
            ->assertJsonPath('data.blocks.0.config.text', 'Reusable banner');
    }

    public function test_inline_data_overrides_referenced_section_data(): void
    {
        $section = Section::factory()->ofType(BlockType::Banner, ['text' => 'Base', 'color' => 'red'])->create();
        $page = Page::factory()->published()->create(['slug' => 'override']);
        $page->blocks()->create([
            'section_id' => $section->id,
            'type' => BlockType::Banner->value,
            'data' => ['text' => 'Overridden'],
            'position' => 0,
        ]);

        $this->getJson('/api/cms/pages/override')
            ->assertOk()
            ->assertJsonPath('data.blocks.0.config.text', 'Overridden')
            ->assertJsonPath('data.blocks.0.config.color', 'red');
    }

    public function test_draft_pages_are_not_publicly_accessible(): void
    {
        Page::factory()->create(['slug' => 'secret']); // draft by default

        $this->getJson('/api/cms/pages/secret')->assertNotFound();
    }

    public function test_index_only_lists_published_pages_of_the_resolved_brand(): void
    {
        $brand = Brand::factory()->create();
        Page::factory()->published()->count(2)->forBrand($brand)->create();
        Page::factory()->count(3)->forBrand($brand)->create();

        $this->getJson('/api/cms/pages?brand=' . $brand->slug)
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_pages_are_scoped_to_the_requested_brand(): void
    {
        $brandA = Brand::factory()->create(['slug' => 'brand-a']);
        $brandB = Brand::factory()->create(['slug' => 'brand-b']);

        // Same slug on both brands: each resolves its own content.
        Page::factory()->published()->forBrand($brandA)->create([
            'slug' => 'home',
            'title' => 'Home A',
        ]);
        Page::factory()->published()->forBrand($brandB)->create([
            'slug' => 'home',
            'title' => 'Home B',
        ]);

        $this->getJson('/api/cms/pages/home?brand=brand-a')
            ->assertOk()
            ->assertJsonPath('data.title', 'Home A');

        $this->getJson('/api/cms/pages/home?brand=brand-b')
            ->assertOk()
            ->assertJsonPath('data.title', 'Home B');

        $this->getJson('/api/cms/pages/home?brand=unknown')->assertNotFound();
    }
}
