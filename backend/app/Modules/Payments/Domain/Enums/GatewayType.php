<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Enums;

/**
 * Supported payment providers.
 */
enum GatewayType: string
{
    case MercadoPago = 'mercadopago';
    case Stripe = 'stripe';
    case OpenPay = 'openpay';

    public function label(): string
    {
        return match ($this) {
            self::MercadoPago => 'MercadoPago',
            self::Stripe => 'Stripe',
            self::OpenPay => 'OpenPay',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $g): string => $g->value, self::cases());
    }
}
