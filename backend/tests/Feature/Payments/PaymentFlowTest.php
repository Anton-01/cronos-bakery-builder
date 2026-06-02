<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Orders\Domain\Enums\OrderStatus;
use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\GatewayConfig;
use App\Modules\Payments\Domain\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function activeStripe(string $secret = 'whsec_test'): GatewayConfig
    {
        return GatewayConfig::factory()->gateway(GatewayType::Stripe)->create([
            'is_active' => true,
            'credentials' => ['public_key' => 'pk', 'secret_key' => 'sk', 'webhook_secret' => $secret],
        ]);
    }

    public function test_guests_cannot_initiate_a_payment(): void
    {
        $this->postJson('/api/payments/initiate', [])->assertUnauthorized();
    }

    public function test_only_active_gateways_are_offered_to_customers(): void
    {
        $this->activeStripe();
        GatewayConfig::factory()->gateway(GatewayType::OpenPay)->create(['is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/payments/gateways')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.gateway', 'stripe');
    }

    public function test_a_customer_can_initiate_a_payment_creating_a_pending_record(): void
    {
        $this->activeStripe();
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'total_amount' => 6800, 'currency' => 'USD']);

        $this->actingAs($user)->postJson('/api/payments/initiate', [
            'order_id' => $order->id,
            'gateway' => 'stripe',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.gateway', 'stripe')
            ->assertJsonPath('data.mode', 'sandbox')
            ->assertJsonPath('data.amount', 6800)
            ->assertJsonStructure(['data' => ['reference'], 'checkout' => ['type']]);

        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'pending']);
        // A "created" traceability event is recorded.
        $payment = Payment::query()->firstOrFail();
        $this->assertDatabaseHas('payment_events', ['payment_id' => $payment->id, 'type' => 'created']);
    }

    public function test_initiating_with_an_inactive_gateway_is_rejected(): void
    {
        // MercadoPago is not configured/active.
        $this->activeStripe();
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->postJson('/api/payments/initiate', [
            'order_id' => $order->id,
            'gateway' => 'mercadopago',
        ])->assertStatus(422)->assertJsonValidationErrors('gateway');
    }

    public function test_a_customer_cannot_pay_for_another_users_order(): void
    {
        $this->activeStripe();
        $order = Order::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs(User::factory()->create())->postJson('/api/payments/initiate', [
            'order_id' => $order->id,
            'gateway' => 'stripe',
        ])->assertNotFound();
    }

    public function test_a_valid_stripe_webhook_marks_the_payment_paid_and_confirms_the_order(): void
    {
        $secret = 'whsec_abc';
        $this->activeStripe($secret);
        $order = Order::factory()->create(['status' => OrderStatus::Pending->value]);
        $payment = Payment::factory()->status(PaymentStatus::Pending)->create([
            'order_id' => $order->id,
            'gateway' => GatewayType::Stripe->value,
            'reference' => 'pi_12345',
        ]);

        $body = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_12345']],
        ]);
        $timestamp = (string) time();
        $signature = 't=' . $timestamp . ',v1=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        $this->call('POST', '/api/payments/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE-SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertOk()->assertJsonPath('status', 'paid');

        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame(OrderStatus::Confirmed, $order->refresh()->status);
        $this->assertDatabaseHas('payment_events', [
            'payment_id' => $payment->id, 'type' => 'webhook', 'signature_valid' => true,
        ]);
    }

    public function test_an_invalid_signature_is_rejected_and_logged(): void
    {
        $this->activeStripe('whsec_real');
        $payment = Payment::factory()->status(PaymentStatus::Pending)->create([
            'gateway' => GatewayType::Stripe->value,
            'reference' => 'pi_999',
        ]);

        $body = json_encode(['type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_999']]]);
        $signature = 't=' . time() . ',v1=deadbeef';

        $this->call('POST', '/api/payments/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE-SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertStatus(400);

        $this->assertSame(PaymentStatus::Pending, $payment->refresh()->status);
        $this->assertDatabaseHas('payment_events', [
            'payment_id' => $payment->id, 'type' => 'webhook', 'signature_valid' => false,
        ]);
    }

    public function test_an_unknown_reference_is_acknowledged_without_processing(): void
    {
        $this->activeStripe('s');

        $body = json_encode(['type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_unknown']]]);
        $signature = 't=' . time() . ',v1=' . hash_hmac('sha256', time() . '.' . $body, 's');

        $this->call('POST', '/api/payments/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE-SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertOk()->assertJsonPath('handled', false);
    }
}
