<?php

declare(strict_types=1);

namespace Tests\Feature\ProductBuilder;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\ProductBuilder\Domain\Enums\OptionType;
use App\Modules\ProductBuilder\Domain\Enums\PriceModifierType;
use App\Modules\ProductBuilder\Domain\Enums\RuleAction;
use App\Modules\ProductBuilder\Domain\Enums\RuleOperator;
use App\Modules\ProductBuilder\Domain\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminProductBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAsProductAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::Administrator->value); // has "manage products"
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_an_admin_can_build_a_product_end_to_end(): void
    {
        $this->actingAsProductAdmin();

        // Create the product.
        $productId = $this->postJson('/api/admin/product-builder/products', [
            'name' => 'Muse Blanc',
            'base_price_amount' => 4000,
        ])->assertCreated()->assertJsonPath('data.slug', 'muse-blanc')->json('data.id');

        // Add an option.
        $optionId = $this->postJson("/api/admin/product-builder/products/{$productId}/options", [
            'key' => 'forma', 'label' => 'Forma', 'type' => OptionType::Radio->value, 'is_required' => true,
        ])->assertCreated()->json('data.id');

        // Add a value with a price modifier.
        $this->postJson("/api/admin/product-builder/products/{$productId}/options/{$optionId}/values", [
            'label' => 'Domo', 'value' => 'domo',
            'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 5500,
        ])->assertCreated()->assertJsonPath('data.price_modifier.amount', 5500);

        $this->assertDatabaseHas('pb_options', ['product_id' => $productId, 'key' => 'forma']);
        $this->assertDatabaseHas('pb_option_values', ['value' => 'domo', 'price_modifier_amount' => 5500]);
    }

    public function test_an_admin_can_attach_a_dependency_rule(): void
    {
        $this->actingAsProductAdmin();
        $product = Product::factory()->create();
        $forma = $product->options()->create(['key' => 'forma', 'label' => 'Forma', 'type' => 'radio']);
        $perlas = $product->options()->create(['key' => 'perlas', 'label' => 'Perlas', 'type' => 'checkbox']);

        $this->postJson("/api/admin/product-builder/products/{$product->id}/rules", [
            'option_id' => $perlas->id,
            'depends_on_option_id' => $forma->id,
            'operator' => RuleOperator::Equals->value,
            'value' => 'domo',
            'action' => RuleAction::Show->value,
        ])->assertCreated();

        $this->assertDatabaseHas('pb_option_rules', [
            'product_id' => $product->id,
            'option_id' => $perlas->id,
            'value' => 'domo',
        ]);
    }

    public function test_a_rule_cannot_reference_options_from_another_product(): void
    {
        $this->actingAsProductAdmin();
        $product = Product::factory()->create();
        $own = $product->options()->create(['key' => 'forma', 'label' => 'Forma', 'type' => 'radio']);
        $foreign = Product::factory()->create()->options()->create(['key' => 'x', 'label' => 'X', 'type' => 'radio']);

        $this->postJson("/api/admin/product-builder/products/{$product->id}/rules", [
            'option_id' => $own->id,
            'depends_on_option_id' => $foreign->id,
            'operator' => RuleOperator::Equals->value,
            'value' => 'y',
            'action' => RuleAction::Show->value,
        ])->assertStatus(422)->assertJsonValidationErrors('depends_on_option_id');
    }

    public function test_an_admin_without_manage_products_is_forbidden(): void
    {
        $courier = Admin::factory()->create();
        $courier->assignRole(AdminRole::Courier->value);
        Sanctum::actingAs($courier);

        $this->postJson('/api/admin/product-builder/products', [
            'name' => 'Nope', 'base_price_amount' => 1000,
        ])->assertForbidden();
    }

    public function test_guests_cannot_manage_products(): void
    {
        $this->postJson('/api/admin/product-builder/products', [])->assertUnauthorized();
    }
}
