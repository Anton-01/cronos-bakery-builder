<?php

declare(strict_types=1);

namespace Tests\Feature\ProductBuilder;

use App\Modules\ProductBuilder\Domain\Enums\OptionType;
use App\Modules\ProductBuilder\Domain\Enums\PriceModifierType;
use App\Modules\ProductBuilder\Domain\Enums\RuleAction;
use App\Modules\ProductBuilder\Domain\Enums\RuleOperator;
use App\Modules\ProductBuilder\Domain\Models\Option;
use App\Modules\ProductBuilder\Domain\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a product: Forma (radio, set price) → optionally shows Perlas;
     * Color (add); Mensaje (textarea).
     */
    private function buildProduct(): Product
    {
        $product = Product::factory()->create([
            'slug' => 'signature-cake',
            'base_price_amount' => 4000,
        ]);

        $forma = $product->options()->create([
            'key' => 'forma', 'label' => 'Forma', 'type' => OptionType::Radio->value,
            'is_required' => true, 'position' => 0,
        ]);
        $forma->values()->createMany([
            ['label' => 'Redonda', 'value' => 'redonda', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 4000, 'position' => 0],
            ['label' => 'Domo', 'value' => 'domo', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 5500, 'position' => 1],
        ]);

        $color = $product->options()->create([
            'key' => 'color', 'label' => 'Color', 'type' => OptionType::Color->value, 'position' => 1,
        ]);
        $color->values()->createMany([
            ['label' => 'Blanco', 'value' => 'blanco', 'position' => 0],
            ['label' => 'Dorado', 'value' => 'dorado', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 600, 'position' => 1],
        ]);

        $perlas = $product->options()->create([
            'key' => 'perlas', 'label' => 'Perlas', 'type' => OptionType::Checkbox->value, 'position' => 2,
        ]);
        $perlas->values()->create(['label' => 'Perlas', 'value' => 'si', 'price_modifier_type' => PriceModifierType::Add->value, 'price_modifier_amount' => 700, 'position' => 0]);

        $product->options()->create([
            'key' => 'mensaje', 'label' => 'Mensaje', 'type' => OptionType::Textarea->value,
            'position' => 3, 'config' => ['max_length' => 20],
        ]);

        // Si Forma = Domo, mostrar Perlas.
        $product->rules()->create([
            'option_id' => $perlas->id,
            'depends_on_option_id' => $forma->id,
            'operator' => RuleOperator::Equals->value,
            'value' => 'domo',
            'action' => RuleAction::Show->value,
        ]);

        return $product;
    }

    public function test_the_full_configuration_is_exposed_publicly(): void
    {
        $this->buildProduct();

        $this->getJson('/api/product-builder/products/signature-cake')
            ->assertOk()
            ->assertJsonPath('data.slug', 'signature-cake')
            ->assertJsonPath('data.options.0.key', 'forma')
            ->assertJsonPath('data.options.0.values.0.value', 'redonda')
            ->assertJsonCount(1, 'data.rules');
    }

    public function test_dynamic_price_sums_modifiers(): void
    {
        $this->buildProduct();

        // Forma=domo (set 5500) + color=dorado (+600) = 6100; perlas visible & selected (+700) = 6800.
        $this->postJson('/api/product-builder/products/signature-cake/quote', [
            'selections' => [
                'forma' => 'domo',
                'color' => 'dorado',
                'perlas' => ['si'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.price.total', 6800)
            ->assertJsonPath('data.price.base', 4000);
    }

    public function test_set_modifier_overrides_the_base_price(): void
    {
        $this->buildProduct();

        // Forma=redonda sets total to 4000 (no other priced selection).
        $this->postJson('/api/product-builder/products/signature-cake/quote', [
            'selections' => ['forma' => 'redonda'],
        ])->assertOk()->assertJsonPath('data.price.total', 4000);
    }

    public function test_perlas_is_hidden_until_forma_is_domo(): void
    {
        $this->buildProduct();

        // Forma=redonda → perlas hidden.
        $hidden = $this->postJson('/api/product-builder/products/signature-cake/quote', [
            'selections' => ['forma' => 'redonda'],
        ])->assertOk();
        $this->assertNotContains('perlas', $hidden->json('data.visible'));

        // Forma=domo → perlas visible.
        $visible = $this->postJson('/api/product-builder/products/signature-cake/quote', [
            'selections' => ['forma' => 'domo'],
        ])->assertOk();
        $this->assertContains('perlas', $visible->json('data.visible'));
    }

    public function test_a_hidden_options_selection_does_not_affect_price(): void
    {
        $this->buildProduct();

        // Perlas selected but Forma=redonda keeps it hidden → its +700 is ignored.
        $this->postJson('/api/product-builder/products/signature-cake/quote', [
            'selections' => ['forma' => 'redonda', 'perlas' => ['si']],
        ])->assertOk()->assertJsonPath('data.price.total', 4000);
    }

    public function test_required_option_must_be_selected(): void
    {
        $this->buildProduct();

        $this->postJson('/api/product-builder/products/signature-cake/quote', [
            'selections' => ['color' => 'blanco'],
        ])->assertStatus(422)->assertJsonValidationErrors('forma');
    }

    public function test_invalid_value_is_rejected(): void
    {
        $this->buildProduct();

        $this->postJson('/api/product-builder/products/signature-cake/quote', [
            'selections' => ['forma' => 'triangular'],
        ])->assertStatus(422)->assertJsonValidationErrors('forma');
    }

    public function test_textarea_respects_max_length(): void
    {
        $this->buildProduct();

        $this->postJson('/api/product-builder/products/signature-cake/quote', [
            'selections' => [
                'forma' => 'redonda',
                'mensaje' => 'this message is definitely longer than twenty chars',
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('mensaje');
    }

    public function test_single_value_option_rejects_multiple_values(): void
    {
        $this->buildProduct();

        $this->postJson('/api/product-builder/products/signature-cake/quote', [
            'selections' => ['forma' => ['redonda', 'domo']],
        ])->assertStatus(422)->assertJsonValidationErrors('forma');
    }

    public function test_only_active_products_are_listed_and_shown(): void
    {
        Product::factory()->create(['slug' => 'active-cake', 'is_active' => true]);
        Product::factory()->inactive()->create(['slug' => 'inactive-cake']);

        $this->getJson('/api/product-builder/products')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/product-builder/products/inactive-cake')->assertNotFound();

        // Guard against accidental coupling between option models.
        $this->assertSame(0, Option::query()->count());
    }
}
