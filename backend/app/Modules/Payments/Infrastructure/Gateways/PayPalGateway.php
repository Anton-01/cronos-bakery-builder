<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Gateways;

use App\Modules\Payments\Application\DTO\ChargeRequest;
use App\Modules\Payments\Application\DTO\ChargeResult;
use App\Modules\Payments\Application\DTO\RefundResult;
use App\Modules\Payments\Application\DTO\WebhookEvent;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\Transaction;
use Illuminate\Support\Str;

/**
 * PayPal strategy (skeleton). Creates an order and redirects the buyer to the
 * approval link. Webhooks are authenticated via HMAC of the body with the
 * configured webhook secret (PayPal's certificate chain verification can be
 * swapped in without touching callers).
 */
class PayPalGateway extends AbstractGateway
{
    public function driver(): string
    {
        return 'paypal';
    }

    public function processPayment(ChargeRequest $request): ChargeResult
    {
        // Skeleton: PayPal order creation. Production wiring pending —
        // both environments simulate through the same code path for now.
        $reference = 'PAYID-' . strtoupper(Str::random(17));
        $base = $this->isProduction() ? 'https://www.paypal.com' : 'https://www.sandbox.paypal.com';

        return new ChargeResult(
            reference: $reference,
            status: PaymentStatus::Pending,
            checkout: [
                'type' => 'redirect',
                'redirect_url' => $base . '/checkoutnow?token=' . $reference,
            ],
            raw: ['order_id' => $reference, 'simulated' => true],
        );
    }

    public function refund(Transaction $transaction, ?int $amount = null): RefundResult
    {
        return new RefundResult(
            providerRefundId: 'PPREF-' . strtoupper(Str::random(12)),
            status: PaymentStatus::Refunded,
            amount: $amount ?? $transaction->amount,
            raw: ['simulated' => true],
        );
    }

    protected function verifySignature(string $payload, array $headers): bool
    {
        $given = $headers['paypal-transmission-sig'] ?? '';
        $expected = $this->hmac($payload, $this->config()->webhookSecret());

        return $this->secureEquals($expected, (string) $given);
    }

    protected function parseWebhook(string $payload): WebhookEvent
    {
        $data = $this->decode($payload);
        $type = (string) ($data['event_type'] ?? '');
        $reference = (string) ($data['resource']['id'] ?? '');

        $status = match ($type) {
            'PAYMENT.CAPTURE.COMPLETED', 'CHECKOUT.ORDER.APPROVED' => PaymentStatus::Paid,
            'PAYMENT.CAPTURE.DENIED' => PaymentStatus::Failed,
            'PAYMENT.CAPTURE.REFUNDED' => PaymentStatus::Refunded,
            default => PaymentStatus::Processing,
        };

        return new WebhookEvent(
            providerEventId: (string) ($data['id'] ?? '') ?: $this->fallbackEventId($payload),
            reference: $reference,
            status: $status,
            eventType: $type ?: 'payment',
            raw: $data,
        );
    }
}
