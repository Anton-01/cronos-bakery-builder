<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Services;

use App\Modules\Payments\Domain\Contracts\PaymentGateway;
use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Infrastructure\Gateways\MercadoPagoGateway;
use App\Modules\Payments\Infrastructure\Gateways\OpenPayGateway;
use App\Modules\Payments\Infrastructure\Gateways\StripeGateway;
use InvalidArgumentException;

/**
 * Resolves the {@see PaymentGateway} strategy for a given provider. New
 * gateways are added here without touching the application services.
 */
final class PaymentGatewayManager
{
    public function __construct(
        private readonly StripeGateway $stripe,
        private readonly MercadoPagoGateway $mercadoPago,
        private readonly OpenPayGateway $openPay,
    ) {
    }

    public function for(GatewayType $gateway): PaymentGateway
    {
        return match ($gateway) {
            GatewayType::Stripe => $this->stripe,
            GatewayType::MercadoPago => $this->mercadoPago,
            GatewayType::OpenPay => $this->openPay,
        };
    }

    public function fromString(string $gateway): PaymentGateway
    {
        $type = GatewayType::tryFrom($gateway)
            ?? throw new InvalidArgumentException("Unknown gateway [{$gateway}].");

        return $this->for($type);
    }
}
