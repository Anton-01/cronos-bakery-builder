<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Modules\Payments\Application\Services\PaymentGatewayManager;
use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Infrastructure\Gateways\MercadoPagoGateway;
use App\Modules\Payments\Infrastructure\Gateways\OpenPayGateway;
use App\Modules\Payments\Infrastructure\Gateways\StripeGateway;
use Tests\TestCase;

class PaymentGatewayStrategyTest extends TestCase
{
    public function test_the_manager_resolves_each_gateway_strategy(): void
    {
        $manager = app(PaymentGatewayManager::class);

        $this->assertInstanceOf(StripeGateway::class, $manager->for(GatewayType::Stripe));
        $this->assertInstanceOf(MercadoPagoGateway::class, $manager->for(GatewayType::MercadoPago));
        $this->assertInstanceOf(OpenPayGateway::class, $manager->for(GatewayType::OpenPay));
        $this->assertInstanceOf(StripeGateway::class, $manager->fromString('stripe'));
    }

    public function test_resolving_an_unknown_gateway_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(PaymentGatewayManager::class)->fromString('paypal');
    }

    public function test_mercadopago_maps_an_approved_webhook_to_paid(): void
    {
        $event = (new MercadoPagoGateway())->parseWebhook(json_encode([
            'type' => 'payment',
            'data' => ['preference_id' => 'pref_1', 'status' => 'approved'],
        ]));

        $this->assertSame('pref_1', $event->reference);
        $this->assertSame(PaymentStatus::Paid, $event->status);
    }

    public function test_openpay_maps_a_failed_charge(): void
    {
        $event = (new OpenPayGateway())->parseWebhook(json_encode([
            'type' => 'charge.failed',
            'transaction' => ['id' => 'trx_1'],
        ]));

        $this->assertSame('trx_1', $event->reference);
        $this->assertSame(PaymentStatus::Failed, $event->status);
    }
}
