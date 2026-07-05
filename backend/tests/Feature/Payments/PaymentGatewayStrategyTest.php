<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Modules\Payments\Application\DTO\ChargeRequest;
use App\Modules\Payments\Application\Services\PaymentGatewayManager;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Exceptions\GatewayException;
use App\Modules\Payments\Domain\Exceptions\GatewayRateLimitException;
use App\Modules\Payments\Domain\Exceptions\GatewayTimeoutException;
use App\Modules\Payments\Domain\Exceptions\UnsupportedDriverException;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use App\Modules\Payments\Infrastructure\Gateways\MercadoPagoGateway;
use App\Modules\Payments\Infrastructure\Gateways\PayPalGateway;
use App\Modules\Payments\Infrastructure\Gateways\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayStrategyTest extends TestCase
{
    use RefreshDatabase;

    private function chargeRequest(): ChargeRequest
    {
        return new ChargeRequest(
            amount: 5000,
            currency: 'USD',
            orderNumber: 'ORD-1',
            idempotencyKey: 'idem-1',
        );
    }

    public function test_the_manager_resolves_strategies_dynamically_from_config(): void
    {
        $manager = app(PaymentGatewayManager::class);

        $this->assertInstanceOf(StripeGateway::class, $manager->driver('stripe'));
        $this->assertInstanceOf(MercadoPagoGateway::class, $manager->driver('mercadopago'));
        $this->assertInstanceOf(PayPalGateway::class, $manager->driver('paypal'));
        $this->assertTrue($manager->supports('openpay'));
        $this->assertNotEmpty($manager->supportedDrivers());
    }

    public function test_resolving_an_unknown_driver_throws(): void
    {
        $this->expectException(UnsupportedDriverException::class);
        app(PaymentGatewayManager::class)->driver('applepay');
    }

    public function test_a_strategy_used_before_initialize_throws(): void
    {
        $this->expectException(GatewayException::class);
        app(PaymentGatewayManager::class)->driver('stripe')->processPayment($this->chargeRequest());
    }

    public function test_mercadopago_maps_an_approved_webhook_to_paid(): void
    {
        $gateway = PaymentGateway::factory()->driver('mercadopago')->create();
        $secret = $gateway->webhookSecret();
        $payload = json_encode([
            'id' => 'evt_mp_1',
            'type' => 'payment',
            'data' => ['preference_id' => 'pref_1', 'status' => 'approved'],
        ]);

        $event = app(PaymentGatewayManager::class)->forGateway($gateway)->handleWebhook(
            $payload,
            ['x-signature' => 'v1=' . hash_hmac('sha256', $payload, $secret)],
        );

        $this->assertSame('pref_1', $event->reference);
        $this->assertSame('evt_mp_1', $event->providerEventId);
        $this->assertSame(PaymentStatus::Paid, $event->status);
    }

    public function test_a_provider_timeout_maps_to_a_typed_exception(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: timed out'));

        $gateway = PaymentGateway::factory()->production()->create();
        $strategy = app(PaymentGatewayManager::class)->forGateway($gateway);

        $this->expectException(GatewayTimeoutException::class);
        $strategy->processPayment($this->chargeRequest());
    }

    public function test_a_provider_rate_limit_maps_to_a_typed_exception_with_retry_after(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['error' => 'rate_limited'], 429, ['Retry-After' => '30'])]);

        $gateway = PaymentGateway::factory()->production()->create();
        $strategy = app(PaymentGatewayManager::class)->forGateway($gateway);

        try {
            $strategy->processPayment($this->chargeRequest());
            $this->fail('Expected GatewayRateLimitException.');
        } catch (GatewayRateLimitException $e) {
            $this->assertSame(30, $e->retryAfterSeconds);
        }
    }

    public function test_a_provider_5xx_maps_to_a_gateway_exception_after_retries(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['error' => 'boom'], 500)]);

        $gateway = PaymentGateway::factory()->production()->create();
        $strategy = app(PaymentGatewayManager::class)->forGateway($gateway);

        $this->expectException(GatewayException::class);
        $strategy->processPayment($this->chargeRequest());
    }
}
