<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Orders\Domain\Enums\OrderStatus;
use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use App\Modules\Payments\Domain\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function activeStripe(string $secret = 'whsec_test'): PaymentGateway
    {
        return PaymentGateway::factory()->create([
            'is_active' => true,
            'credentials' => ['public_key' => 'pk', 'secret_key' => 'sk', 'webhook_secret' => $secret],
        ]);
    }

    /**
     * @return array{0: string, 1: string} [body, signature header]
     */
    private function stripeEvent(string $eventId, string $reference, string $secret, string $type = 'payment_intent.succeeded'): array
    {
        $body = json_encode([
            'id' => $eventId,
            'type' => $type,
            'data' => ['object' => ['id' => $reference]],
        ]);
        $timestamp = (string) time();
        $signature = 't=' . $timestamp . ',v1=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        return [$body, $signature];
    }

    private function postWebhook(PaymentGateway $gateway, string $body, string $signature)
    {
        return $this->call('POST', "/api/payments/webhooks/stripe/{$gateway->id}", [], [], [], [
            'HTTP_STRIPE-SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    public function test_guests_cannot_initiate_a_payment(): void
    {
        $this->postJson('/api/payments/initiate', [])->assertUnauthorized();
    }

    public function test_only_active_gateways_are_offered_to_customers(): void
    {
        $this->activeStripe();
        PaymentGateway::factory()->driver('openpay')->inactive()->create();

        $this->actingAs(User::factory()->create())
            ->getJson('/api/payments/gateways')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.gateway', 'stripe');
    }

    public function test_a_customer_can_initiate_a_payment_creating_a_pending_transaction(): void
    {
        $gateway = $this->activeStripe();
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'total_amount' => 6800, 'currency' => 'USD']);

        $this->actingAs($user)->postJson('/api/payments/initiate', [
            'order_id' => $order->id,
            'gateway' => 'stripe',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_gateway_id', $gateway->id)
            ->assertJsonPath('data.amount', 6800)
            ->assertJsonStructure(['data' => ['provider_transaction_id'], 'checkout' => ['type']]);

        $this->assertDatabaseHas('transactions', ['order_id' => $order->id, 'status' => 'pending']);
        // A "created" traceability event is recorded.
        $transaction = Transaction::query()->firstOrFail();
        $this->assertDatabaseHas('transaction_events', ['transaction_id' => $transaction->id, 'type' => 'created']);
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

    public function test_a_valid_stripe_webhook_marks_the_transaction_paid_and_confirms_the_order(): void
    {
        $secret = 'whsec_abc';
        $gateway = $this->activeStripe($secret);
        $order = Order::factory()->create(['status' => OrderStatus::Pending->value]);
        $transaction = Transaction::factory()->status(PaymentStatus::Pending)->create([
            'order_id' => $order->id,
            'payment_gateway_id' => $gateway->id,
            'provider_transaction_id' => 'pi_12345',
        ]);

        [$body, $signature] = $this->stripeEvent('evt_1', 'pi_12345', $secret);

        $this->postWebhook($gateway, $body, $signature)
            ->assertOk()
            ->assertJsonPath('status', 'paid');

        $this->assertSame(PaymentStatus::Paid, $transaction->refresh()->status);
        $this->assertNotNull($transaction->paid_at);
        $this->assertSame(OrderStatus::Confirmed, $order->refresh()->status);
        $this->assertDatabaseHas('transaction_events', [
            'transaction_id' => $transaction->id, 'type' => 'webhook', 'signature_valid' => true,
        ]);
    }

    public function test_webhooks_are_idempotent_duplicate_events_are_ignored(): void
    {
        $secret = 'whsec_dup';
        $gateway = $this->activeStripe($secret);
        $transaction = Transaction::factory()->status(PaymentStatus::Pending)->create([
            'payment_gateway_id' => $gateway->id,
            'provider_transaction_id' => 'pi_777',
        ]);

        [$body, $signature] = $this->stripeEvent('evt_unique_1', 'pi_777', $secret);

        // First delivery processes the event.
        $this->postWebhook($gateway, $body, $signature)->assertOk()->assertJsonPath('handled', true);
        // Second (duplicate) delivery is acknowledged but NOT reprocessed.
        $this->postWebhook($gateway, $body, $signature)->assertOk()->assertJsonPath('status', 'duplicate');

        // Exactly one webhook event applied to the transaction; ledger has one row.
        $this->assertSame(1, $transaction->events()->where('type', 'webhook')->count());
        $this->assertDatabaseCount('gateway_webhook_events', 1);
    }

    public function test_an_invalid_signature_is_rejected_without_state_changes(): void
    {
        $gateway = $this->activeStripe('whsec_real');
        $transaction = Transaction::factory()->status(PaymentStatus::Pending)->create([
            'payment_gateway_id' => $gateway->id,
            'provider_transaction_id' => 'pi_999',
        ]);

        $body = json_encode(['id' => 'evt_x', 'type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_999']]]);
        $signature = 't=' . time() . ',v1=deadbeef';

        $this->postWebhook($gateway, $body, $signature)->assertStatus(400);

        $this->assertSame(PaymentStatus::Pending, $transaction->refresh()->status);
        // Nothing is written when authenticity cannot be proven.
        $this->assertDatabaseCount('gateway_webhook_events', 0);
        $this->assertSame(0, $transaction->events()->count());
    }

    public function test_an_unknown_reference_is_acknowledged_without_processing(): void
    {
        $secret = 's';
        $gateway = $this->activeStripe($secret);

        [$body, $signature] = $this->stripeEvent('evt_unknown', 'pi_unknown', $secret);

        $this->postWebhook($gateway, $body, $signature)->assertOk()->assertJsonPath('handled', false);
    }

    public function test_webhooks_for_inactive_or_mismatched_gateways_are_rejected(): void
    {
        $gateway = PaymentGateway::factory()->inactive()->create();

        $this->postWebhook($gateway, '{}', 't=1,v1=x')->assertNotFound();
    }
}
