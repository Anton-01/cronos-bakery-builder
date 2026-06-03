<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Orders\Domain\Models\Address;
use App\Modules\Orders\Domain\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressAndOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manage_addresses(): void
    {
        $this->getJson('/api/addresses')->assertUnauthorized();
    }

    public function test_a_customer_can_save_multiple_labelled_addresses(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach (['home', 'work', 'other'] as $label) {
            $this->postJson('/api/addresses', [
                'label' => $label,
                'recipient_name' => 'Markus',
                'line1' => 'Calle 1',
                'city' => 'San José',
            ])->assertCreated()->assertJsonPath('data.label', $label);
        }

        $this->getJson('/api/addresses')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_the_first_address_becomes_default_and_setting_a_new_default_unsets_others(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $first = $this->postJson('/api/addresses', [
            'label' => 'home', 'recipient_name' => 'A', 'line1' => 'L1', 'city' => 'SJ',
        ])->assertCreated()->json('data');
        $this->assertTrue($first['is_default']);

        $second = $this->postJson('/api/addresses', [
            'label' => 'work', 'recipient_name' => 'B', 'line1' => 'L2', 'city' => 'SJ', 'is_default' => true,
        ])->json('data');

        $this->assertTrue(Address::find($second['id'])->is_default);
        $this->assertFalse(Address::find($first['id'])->is_default);
    }

    public function test_a_customer_only_sees_their_own_addresses(): void
    {
        $mine = Address::factory()->create(['user_id' => User::factory()->create()->id]);
        $other = Address::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($mine->user);
        $this->getJson('/api/addresses')->assertOk()->assertJsonCount(1, 'data');
        $this->putJson("/api/addresses/{$other->id}", [
            'label' => 'home', 'recipient_name' => 'X', 'line1' => 'Y', 'city' => 'Z',
        ])->assertNotFound();
    }

    public function test_order_history_is_scoped_to_the_customer(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(2)->create(['user_id' => $user->id]);
        Order::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($user);
        $this->getJson('/api/orders')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_customer_cannot_view_another_users_order(): void
    {
        $order = Order::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs(User::factory()->create());
        $this->getJson("/api/orders/{$order->id}")->assertNotFound();
    }

    public function test_public_branches_are_listed(): void
    {
        \App\Modules\Orders\Domain\Models\Branch::factory()->count(2)->create();
        \App\Modules\Orders\Domain\Models\Branch::factory()->inactive()->create();

        $this->getJson('/api/branches')->assertOk()->assertJsonCount(2, 'data');
    }
}
