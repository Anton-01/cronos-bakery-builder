<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Catalog\Domain\Models\Attribute;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAsCatalogAdmin(): void
    {
        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::Administrator->value); // has "manage products"
        Sanctum::actingAs($admin);
    }

    public function test_an_admin_can_create_a_category(): void
    {
        $this->actingAsCatalogAdmin();

        $this->postJson('/api/admin/catalog/categories', ['name' => 'Floral'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'floral');

        $this->assertDatabaseHas('catalog_categories', ['slug' => 'floral']);
    }

    public function test_an_admin_can_define_a_filterable_attribute_with_values(): void
    {
        $this->actingAsCatalogAdmin();

        $attributeId = $this->postJson('/api/admin/catalog/attributes', [
            'name' => 'Tamaño', 'type' => 'select', 'is_filterable' => true,
        ])->assertCreated()->assertJsonPath('data.code', 'tamano')->json('data.id');

        $this->postJson("/api/admin/catalog/attributes/{$attributeId}/values", [
            'label' => 'Grande',
        ])->assertCreated();

        $this->assertDatabaseHas('catalog_attribute_values', ['value' => 'grande']);
    }

    public function test_an_admin_can_assign_taxonomy_to_a_product(): void
    {
        $this->actingAsCatalogAdmin();
        $product = Product::factory()->create();
        $category = Category::factory()->create();
        $attr = Attribute::factory()->create();
        $value = $attr->values()->create(['label' => 'Grande', 'value' => 'grande']);

        $this->putJson("/api/admin/catalog/products/{$product->id}/taxonomy", [
            'categories' => [$category->id],
            'primary_category' => $category->id,
            'attribute_values' => [$value->id],
        ])->assertOk();

        $this->assertDatabaseHas('catalog_category_product', [
            'product_id' => $product->id,
            'category_id' => $category->id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('catalog_attribute_value_product', [
            'product_id' => $product->id,
            'attribute_value_id' => $value->id,
        ]);
    }

    public function test_primary_category_must_be_one_of_the_assigned_categories(): void
    {
        $this->actingAsCatalogAdmin();
        $product = Product::factory()->create();
        $a = Category::factory()->create();
        $b = Category::factory()->create();

        $this->putJson("/api/admin/catalog/products/{$product->id}/taxonomy", [
            'categories' => [$a->id],
            'primary_category' => $b->id,
        ])->assertStatus(422)->assertJsonValidationErrors('primary_category');
    }

    public function test_a_courier_cannot_manage_taxonomy(): void
    {
        $courier = Admin::factory()->create();
        $courier->assignRole(AdminRole::Courier->value);
        Sanctum::actingAs($courier);

        $this->postJson('/api/admin/catalog/categories', ['name' => 'Nope'])->assertForbidden();
    }

    public function test_guests_cannot_manage_taxonomy(): void
    {
        $this->postJson('/api/admin/catalog/categories', [])->assertUnauthorized();
    }
}
