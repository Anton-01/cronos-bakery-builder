<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\DTO;

use App\Modules\Payments\Domain\Enums\PaymentStatus;

/**
 * Result of creating a charge: the gateway reference plus the actionable
 * payload (redirect URL or client secret) the frontend uses to complete it.
 */
final class ChargeResult
{
    /**
     * @param  array<string, mixed>  $checkout
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $reference,
        public readonly PaymentStatus $status,
        public readonly array $checkout,
        public readonly array $raw = [],
    ) {
    }
}
