<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\ProductBuilder\Domain\Enums\OptionType;
use App\Modules\ProductBuilder\Domain\Enums\PriceModifierType;
use App\Modules\ProductBuilder\Domain\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function buildProduct(): Product
    {
        $product = Product::factory()->create(['slug' => 'signature-cake', 'base_price_amount' => 4000]);
        $forma = $product->options()->create([
            'key' => 'forma', 'label' => 'Forma', 'type' => OptionType::Radio->value, 'is_required' => true,
        ]);
        $forma->values()->createMany([
            ['label' => 'Redonda', 'value' => 'redonda', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 4000, 'position' => 0],
            ['label' => 'Domo', 'value' => 'domo', 'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 5500, 'position' => 1],
        ]);

        return $product;
    }

    public function test_guests_cannot_access_the_cart(): void
    {
        $this->getJson('/api/cart')->assertUnauthorized();
        $this->postJson('/api/cart/items', [])->assertUnauthorized();
    }

    public function test_a_customer_can_add_a_configured_cake_with_server_priced_total(): void
    {
        $this->buildProduct();
        $this->actingAs(User::factory()->create());

        $this->postJson('/api/cart/items', [
            'product_slug' => 'signature-cake',
            'selections' => ['forma' => 'domo'],
            'quantity' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.items.0.product_name', $this->buildProductName())
            ->assertJsonPath('data.items.0.unit_price.amount', 5500)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.summary.subtotal.amount', 11000);
    }

    private function buildProductName(): string
    {
        return Product::whereSlug('signature-cake')->first()->name;
    }

    public function test_adding_an_invalid_configuration_is_rejected(): void
    {
        $this->buildProduct();
        $this->actingAs(User::factory()->create());

        // Missing the required "forma" option.
        $this->postJson('/api/cart/items', [
            'product_slug' => 'signature-cake',
            'selections' => [],
        ])->assertStatus(422)->assertJsonValidationErrors('forma');
    }

    public function test_a_customer_can_update_and_remove_cart_items(): void
    {
        $this->buildProduct();
        $user = User::factory()->create();
        $this->actingAs($user);

        $cart = $this->postJson('/api/cart/items', [
            'product_slug' => 'signature-cake',
            'selections' => ['forma' => 'redonda'],
        ])->json('data');
        $itemId = $cart['items'][0]['id'];

        $this->putJson("/api/cart/items/{$itemId}", ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('data.summary.subtotal.amount', 12000);

        $this->deleteJson("/api/cart/items/{$itemId}")
            ->assertOk()
            ->assertJsonPath('data.item_count', 0);
    }

    public function test_a_cart_is_isolated_per_customer(): void
    {
        $this->buildProduct();
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $itemId = $this->postJson('/api/cart/items', [
            'product_slug' => 'signature-cake', 'selections' => ['forma' => 'redonda'],
        ])->json('data.items.0.id');

        // Another customer cannot touch the first customer's item.
        $this->actingAs(User::factory()->create());
        $this->putJson("/api/cart/items/{$itemId}", ['quantity' => 5])->assertNotFound();
    }
}
