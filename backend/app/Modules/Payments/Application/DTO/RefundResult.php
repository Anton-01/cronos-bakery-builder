<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\DTO;

use App\Modules\Payments\Domain\Enums\PaymentStatus;

/**
 * Result of a refund request against a provider.
 */
final class RefundResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $providerRefundId,
        public readonly PaymentStatus $status,
        public readonly int $amount,
        public readonly array $raw = [],
    ) {
    }
}
