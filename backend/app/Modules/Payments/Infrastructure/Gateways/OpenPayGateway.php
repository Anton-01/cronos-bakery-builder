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
 * OpenPay strategy (skeleton). Creates a charge with a redirect (3DS / hosted)
 * URL and authenticates webhooks via an `Openpay-Signature` HMAC of the body.
 */
class OpenPayGateway extends AbstractGateway
{
    public function driver(): string
    {
        return 'openpay';
    }

    public function processPayment(ChargeRequest $request): ChargeResult
    {
        // Skeleton: OpenPay hosted charge. Production wiring pending —
        // both environments simulate through the same code path for now.
        $reference = 'trx_' . Str::random(20);

        return new ChargeResult(
            reference: $reference,
            status: PaymentStatus::Pending,
            checkout: [
                'type' => 'redirect',
                'redirect_url' => 'https://sandbox-api.openpay.mx/redirect/' . $reference,
            ],
            raw: ['transaction_id' => $reference, 'simulated' => true],
        );
    }

    public function refund(Transaction $transaction, ?int $amount = null): RefundResult
    {
        return new RefundResult(
            providerRefundId: 'opref_' . Str::random(16),
            status: PaymentStatus::Refunded,
            amount: $amount ?? $transaction->amount,
            raw: ['simulated' => true],
        );
    }

    protected function verifySignature(string $payload, array $headers): bool
    {
        $given = $headers['openpay-signature'] ?? '';
        $expected = $this->hmac($payload, $this->config()->webhookSecret());

        return $this->secureEquals($expected, (string) $given);
    }

    protected function parseWebhook(string $payload): WebhookEvent
    {
        $data = $this->decode($payload);
        $type = (string) ($data['type'] ?? '');
        $reference = (string) ($data['transaction']['id'] ?? $data['transaction']['order_id'] ?? '');

        $status = match ($type) {
            'charge.succeeded' => PaymentStatus::Paid,
            'charge.failed' => PaymentStatus::Failed,
            'charge.refunded' => PaymentStatus::Refunded,
            'charge.cancelled' => PaymentStatus::Cancelled,
            default => $this->mapStatus((string) ($data['transaction']['status'] ?? '')),
        };

        return new WebhookEvent(
            providerEventId: (string) ($data['event_id'] ?? '') ?: $this->fallbackEventId($payload),
            reference: $reference,
            status: $status,
            eventType: $type ?: 'charge',
            raw: $data,
        );
    }
}
