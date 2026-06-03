<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Orders\Domain\Models\Address;
use App\Modules\Orders\Domain\Models\Branch;
use App\Modules\ProductBuilder\Domain\Enums\OptionType;
use App\Modules\ProductBuilder\Domain\Enums\PriceModifierType;
use App\Modules\ProductBuilder\Domain\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function seedProduct(): void
    {
        $product = Product::factory()->create(['slug' => 'signature-cake', 'base_price_amount' => 4000]);
        $forma = $product->options()->create([
            'key' => 'forma', 'label' => 'Forma', 'type' => OptionType::Radio->value, 'is_required' => true,
        ]);
        $forma->values()->create([
            'label' => 'Domo', 'value' => 'domo',
            'price_modifier_type' => PriceModifierType::Set->value, 'price_modifier_amount' => 5500,
        ]);
    }

    private function addToCart(User $user): void
    {
        $this->actingAs($user)->postJson('/api/cart/items', [
            'product_slug' => 'signature-cake',
            'selections' => ['forma' => 'domo'],
            'quantity' => 1,
        ])->assertOk();
    }

    public function test_guests_cannot_checkout(): void
    {
        $this->postJson('/api/checkout', [])->assertUnauthorized();
    }

    public function test_checkout_fails_with_an_empty_cart(): void
    {
        $branch = Branch::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->postJson('/api/checkout', [
            'fulfillment_type' => 'pickup',
            'branch_id' => $branch->id,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_time' => '10:00',
        ])->assertStatus(422)->assertJsonValidationErrors('cart');
    }

    public function test_a_customer_can_checkout_for_pickup(): void
    {
        $this->seedProduct();
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        $this->addToCart($user);

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'fulfillment_type' => 'pickup',
            'branch_id' => $branch->id,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_time' => '10:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.fulfillment.type', 'pickup')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.totals.total', 5500)
            ->assertJsonCount(1, 'data.items');

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'fulfillment_type' => 'pickup']);
        // Cart is emptied after checkout.
        $this->assertSame(0, $this->actingAs($user)->getJson('/api/cart')->json('data.item_count'));
    }

    public function test_a_customer_can_checkout_for_delivery_and_address_is_snapshotted(): void
    {
        $this->seedProduct();
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id, 'city' => 'San José']);
        $this->addToCart($user);

        $this->actingAs($user)->postJson('/api/checkout', [
            'fulfillment_type' => 'delivery',
            'address_id' => $address->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.fulfillment.type', 'delivery')
            ->assertJsonPath('data.fulfillment.shipping_address.city', 'San José');
    }

    public function test_delivery_requires_an_address(): void
    {
        $this->seedProduct();
        $user = User::factory()->create();
        $this->addToCart($user);

        $this->actingAs($user)->postJson('/api/checkout', [
            'fulfillment_type' => 'delivery',
        ])->assertStatus(422)->assertJsonValidationErrors('address_id');
    }

    public function test_pickup_date_cannot_be_in_the_past(): void
    {
        $this->seedProduct();
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        $this->addToCart($user);

        $this->actingAs($user)->postJson('/api/checkout', [
            'fulfillment_type' => 'pickup',
            'branch_id' => $branch->id,
            'pickup_date' => now()->subDay()->toDateString(),
            'pickup_time' => '10:00',
        ])->assertStatus(422)->assertJsonValidationErrors('pickup_date');
    }

    public function test_a_customer_cannot_checkout_with_another_users_address(): void
    {
        $this->seedProduct();
        $user = User::factory()->create();
        $foreignAddress = Address::factory()->create(['user_id' => User::factory()->create()->id]);
        $this->addToCart($user);

        // Address exists (passes validation) but is not owned → 404 from the service.
        $this->actingAs($user)->postJson('/api/checkout', [
            'fulfillment_type' => 'delivery',
            'address_id' => $foreignAddress->id,
        ])->assertNotFound();
    }
}
