<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_landing_returns_seo_breadcrumbs_and_products(): void
    {
        $parent = Category::factory()->create(['name' => 'Pasteles', 'slug' => 'pasteles']);
        $floral = Category::factory()->child($parent)->create([
            'name' => 'Floral', 'slug' => 'floral', 'meta_title' => 'Pasteles Florales',
        ]);

        Product::factory()->create(['name' => 'Rosa'])->categories()->attach($floral->id, ['is_primary' => true]);

        $response = $this->getJson('/api/catalog/categories/floral')->assertOk();

        $response->assertJsonPath('data.category.slug', 'floral')
            ->assertJsonPath('data.category.url', '/categoria/floral')
            ->assertJsonPath('data.category.seo.meta_title', 'Pasteles Florales')
            ->assertJsonPath('data.breadcrumbs.0.type', 'catalog')
            ->assertJsonPath('data.breadcrumbs.1.slug', 'pasteles')
            ->assertJsonPath('data.breadcrumbs.2.slug', 'floral')
            ->assertJsonCount(1, 'data.products.data');
    }

    public function test_product_detail_by_slug_returns_seo_and_breadcrumbs(): void
    {
        $category = Category::factory()->create(['name' => 'Floral', 'slug' => 'floral']);
        $product = Product::factory()->create([
            'name' => 'Muse Blanc', 'slug' => 'muse-blanc', 'meta_title' => 'Muse Blanc Cake',
        ]);
        $product->categories()->attach($category->id, ['is_primary' => true]);

        $this->getJson('/api/catalog/detail/muse-blanc')
            ->assertOk()
            ->assertJsonPath('data.product.slug', 'muse-blanc')
            ->assertJsonPath('data.product.url', '/pastel/muse-blanc')
            ->assertJsonPath('data.product.seo.meta_title', 'Muse Blanc Cake')
            ->assertJsonPath('data.breadcrumbs.1.slug', 'floral')
            ->assertJsonPath('data.breadcrumbs.2.slug', 'muse-blanc');
    }

    public function test_unknown_slugs_return_404(): void
    {
        $this->getJson('/api/catalog/categories/nope')->assertNotFound();
        $this->getJson('/api/catalog/detail/nope')->assertNotFound();
    }

    public function test_inactive_category_is_not_reachable(): void
    {
        Category::factory()->create(['slug' => 'hidden', 'is_active' => false]);

        $this->getJson('/api/catalog/categories/hidden')->assertNotFound();
    }
}
