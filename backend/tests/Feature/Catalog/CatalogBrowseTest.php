<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Domain\Models\Attribute;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Collection;
use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_can_be_filtered_by_category(): void
    {
        $floral = Category::factory()->create(['slug' => 'floral']);
        $moderno = Category::factory()->create(['slug' => 'moderno']);

        Product::factory()->create(['name' => 'Rosa'])->categories()->attach($floral->id, ['is_primary' => true]);
        Product::factory()->create(['name' => 'Cubo'])->categories()->attach($moderno->id, ['is_primary' => true]);

        $this->getJson('/api/catalog/browse?category=floral')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Rosa');
    }

    public function test_products_can_be_filtered_by_attribute_value(): void
    {
        $size = Attribute::factory()->create(['code' => 'tamano']);
        $small = $size->values()->create(['label' => 'Pequeño', 'value' => 'pequeno']);
        $large = $size->values()->create(['label' => 'Grande', 'value' => 'grande']);

        Product::factory()->create(['name' => 'Mini'])->attributeValues()->attach($small->id);
        Product::factory()->create(['name' => 'Maxi'])->attributeValues()->attach($large->id);

        $this->getJson('/api/catalog/browse?attributes[tamano]=grande')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Maxi');
    }

    public function test_products_can_be_filtered_by_price_range_and_sorted(): void
    {
        Product::factory()->create(['name' => 'Cheap', 'price_amount' => 1000]);
        Product::factory()->create(['name' => 'Mid', 'price_amount' => 3000]);
        Product::factory()->create(['name' => 'Pricey', 'price_amount' => 9000]);

        $response = $this->getJson('/api/catalog/browse?price_min=2000&price_max=8000&sort=price_desc')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mid');

        // Sorting across the full set.
        $sorted = $this->getJson('/api/catalog/browse?sort=price_asc')->json('data');
        $this->assertSame('Cheap', $sorted[0]['name']);
        $this->assertSame('Pricey', $sorted[2]['name']);
    }

    public function test_multiple_attribute_filters_are_combined_with_and(): void
    {
        $size = Attribute::factory()->create(['code' => 'tamano']);
        $flavor = Attribute::factory()->create(['code' => 'sabor']);
        $grande = $size->values()->create(['label' => 'Grande', 'value' => 'grande']);
        $choco = $flavor->values()->create(['label' => 'Chocolate', 'value' => 'chocolate']);

        $match = Product::factory()->create(['name' => 'Match']);
        $match->attributeValues()->attach([$grande->id, $choco->id]);
        Product::factory()->create(['name' => 'OnlySize'])->attributeValues()->attach($grande->id);

        $this->getJson('/api/catalog/browse?attributes[tamano]=grande&attributes[sabor]=chocolate')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Match');
    }

    public function test_inactive_products_are_excluded(): void
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $this->getJson('/api/catalog/browse')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_facets_expose_configurable_filters(): void
    {
        Category::factory()->create(['name' => 'Floral']);
        Collection::factory()->create(['name' => 'Temporada']);
        $attr = Attribute::factory()->create(['code' => 'tamano', 'is_filterable' => true]);
        $attr->values()->create(['label' => 'Grande', 'value' => 'grande']);
        Attribute::factory()->notFilterable()->create(['code' => 'hidden']);
        Product::factory()->create(['price_amount' => 4200]);

        $this->getJson('/api/catalog/facets')
            ->assertOk()
            ->assertJsonCount(1, 'data.categories')
            ->assertJsonCount(1, 'data.collections')
            ->assertJsonCount(1, 'data.attributes') // only filterable
            ->assertJsonPath('data.attributes.0.code', 'tamano')
            ->assertJsonPath('data.price.max', 4200);
    }
}
