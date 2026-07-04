<?php

declare(strict_types=1);

namespace Tests\Feature\ProductBuilder;

use App\Modules\Administration\Domain\Enums\AdminRole;
use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\ProductBuilder\Application\Services\PreviewTokenService;
use App\Modules\ProductBuilder\Domain\Enums\OptionType;
use App\Modules\ProductBuilder\Domain\Models\OptionTemplate;
use App\Modules\ProductBuilder\Domain\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Covers value exclusions on product↔template links and the tokenized
 * storefront preview flow (draft products included).
 */
class OptionExclusionAndPreviewTest extends TestCase
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
        $admin->assignRole(AdminRole::Administrator->value);
        Sanctum::actingAs($admin);

        return $admin;
    }

    /** @return array{product: Product, template: OptionTemplate} */
    private function makeProductWithTemplate(bool $active = true): array
    {
        $product = Product::factory()->create([
            'name' => 'Pastel Prueba',
            'slug' => 'pastel-prueba',
            'is_active' => $active,
        ]);

        $template = OptionTemplate::create([
            'key' => 'forma',
            'label' => 'Forma',
            'type' => OptionType::Radio->value,
        ]);

        foreach (['Redonda', 'Cuadrada', 'Domo'] as $i => $label) {
            $template->values()->create([
                'label' => $label,
                'value' => strtolower($label),
                'position' => $i,
            ]);
        }

        return ['product' => $product, 'template' => $template->fresh('values')];
    }

    public function test_admin_can_exclude_template_values_per_product(): void
    {
        $this->actingAsProductAdmin();
        ['product' => $product, 'template' => $template] = $this->makeProductWithTemplate();

        $domo = $template->values->firstWhere('label', 'Domo');

        // Link the template excluding "Domo".
        $link = $this->postJson("/api/admin/product-builder/products/{$product->id}/option-links", [
            'template_id' => $template->id,
            'excluded_value_ids' => [$domo->id],
        ])->assertCreated()->json('data');

        $this->assertSame([$domo->id], $link['excluded_value_ids']);
        // Effective values are filtered.
        $this->assertSame(['Redonda', 'Cuadrada'], array_column($link['values'], 'label'));
        // The full template value list is still available for the admin UI.
        $this->assertCount(3, $link['template']['values']);

        // Clearing exclusions restores inheritance of every value.
        $updated = $this->putJson(
            "/api/admin/product-builder/products/{$product->id}/option-links/{$link['id']}",
            ['excluded_value_ids' => null],
        )->assertOk()->json('data');

        $this->assertNull($updated['excluded_value_ids']);
        $this->assertCount(3, $updated['values']);
    }

    public function test_excluded_values_must_belong_to_the_linked_template(): void
    {
        $this->actingAsProductAdmin();
        ['product' => $product, 'template' => $template] = $this->makeProductWithTemplate();

        $other = OptionTemplate::create([
            'key' => 'sabor',
            'label' => 'Sabor',
            'type' => OptionType::Select->value,
        ]);
        $foreignValue = $other->values()->create(['label' => 'Vainilla', 'value' => 'vainilla']);

        $this->postJson("/api/admin/product-builder/products/{$product->id}/option-links", [
            'template_id' => $template->id,
            'excluded_value_ids' => [$foreignValue->id],
        ])->assertUnprocessable()->assertJsonValidationErrors(['excluded_value_ids.0']);
    }

    public function test_guest_can_view_a_draft_product_with_a_valid_preview_token(): void
    {
        ['product' => $product] = $this->makeProductWithTemplate(active: false);

        $token = app(PreviewTokenService::class)->mint($product->id);

        // No admin session in this "tab": the token is the only credential.
        $this->getJson("/api/product-builder/preview/{$token}")
            ->assertOk()
            ->assertJsonPath('data.slug', 'pastel-prueba');

        $this->getJson('/api/product-builder/preview/invalid-token')->assertForbidden();

        // Without a token, the draft product stays hidden from the storefront.
        $this->getJson('/api/product-builder/products/pastel-prueba')->assertNotFound();
    }

    public function test_preview_token_unlocks_quotes_for_draft_products(): void
    {
        ['product' => $product] = $this->makeProductWithTemplate(active: false);

        $token = app(PreviewTokenService::class)->mint($product->id);

        $this->postJson('/api/product-builder/products/pastel-prueba/quote', [
            'selections' => [],
            'preview_token' => $token,
        ])->assertOk()->assertJsonPath('data.product', 'pastel-prueba');

        $this->postJson('/api/product-builder/products/pastel-prueba/quote', [
            'selections' => [],
        ])->assertNotFound();
    }

    public function test_admin_endpoint_mints_preview_tokens(): void
    {
        $this->actingAsProductAdmin();
        ['product' => $product] = $this->makeProductWithTemplate(active: false);

        $token = $this->postJson("/api/admin/product-builder/products/{$product->id}/preview-token")
            ->assertOk()
            ->json('data.token');

        $this->assertNotEmpty($token);
        $this->assertSame($product->id, app(PreviewTokenService::class)->resolve($token));
    }
}
