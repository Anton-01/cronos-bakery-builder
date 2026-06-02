<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\DTO;

/**
 * Input for creating a charge with a gateway.
 */
final class ChargeRequest
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $orderNumber,
        public readonly string $idempotencyKey,
        public readonly ?string $customerEmail = null,
        public readonly ?string $returnUrl = null,
    ) {
    }
}
