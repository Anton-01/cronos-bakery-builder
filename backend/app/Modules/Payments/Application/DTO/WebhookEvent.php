<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\DTO;

use App\Modules\Payments\Domain\Enums\PaymentStatus;

/**
 * Normalised representation of a gateway webhook notification.
 */
final class WebhookEvent
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $reference,
        public readonly PaymentStatus $status,
        public readonly string $eventType,
        public readonly array $raw = [],
    ) {
    }
}
