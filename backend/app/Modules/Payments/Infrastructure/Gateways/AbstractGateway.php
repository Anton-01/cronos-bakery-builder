<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Gateways;

use App\Modules\Payments\Domain\Contracts\PaymentGateway;
use App\Modules\Payments\Domain\Enums\PaymentStatus;

/**
 * Shared helpers for gateway strategies (HMAC signing, status mapping).
 */
abstract class AbstractGateway implements PaymentGateway
{
    protected function hmac(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    protected function secureEquals(string $known, string $given): bool
    {
        return $known !== '' && hash_equals($known, $given);
    }

    /**
     * Decode a JSON webhook body to an array.
     *
     * @return array<string, mixed>
     */
    protected function decode(string $payload): array
    {
        return json_decode($payload, true) ?: [];
    }

    protected function mapStatus(string $value): PaymentStatus
    {
        return match ($value) {
            'paid', 'approved', 'succeeded', 'completed' => PaymentStatus::Paid,
            'failed', 'rejected', 'declined' => PaymentStatus::Failed,
            'refunded' => PaymentStatus::Refunded,
            'cancelled', 'canceled' => PaymentStatus::Cancelled,
            'processing', 'in_process' => PaymentStatus::Processing,
            default => PaymentStatus::Pending,
        };
    }
}
