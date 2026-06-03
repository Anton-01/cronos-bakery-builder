<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Gateways;

use App\Modules\Payments\Application\DTO\ChargeRequest;
use App\Modules\Payments\Application\DTO\ChargeResult;
use App\Modules\Payments\Application\DTO\WebhookEvent;
use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\GatewayConfig;
use Illuminate\Support\Str;

/**
 * OpenPay strategy. Creates a charge with a redirect (3DS / hosted) URL and
 * authenticates webhooks via an `Openpay-Signature` HMAC of the body.
 */
class OpenPayGateway extends AbstractGateway
{
    public function type(): GatewayType
    {
        return GatewayType::OpenPay;
    }

    public function createCharge(ChargeRequest $request, GatewayConfig $config): ChargeResult
    {
        $reference = 'trx_' . Str::random(20);

        return new ChargeResult(
            reference: $reference,
            status: PaymentStatus::Pending,
            checkout: [
                'type' => 'redirect',
                'redirect_url' => 'https://sandbox-api.openpay.mx/redirect/' . $reference,
            ],
            raw: ['transaction_id' => $reference],
        );
    }

    public function verifySignature(string $payload, array $headers, GatewayConfig $config): bool
    {
        $given = $headers['openpay-signature'] ?? '';
        $expected = $this->hmac($payload, $config->webhookSecret());

        return $this->secureEquals($expected, (string) $given);
    }

    public function parseWebhook(string $payload): WebhookEvent
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

        return new WebhookEvent($reference, $status, $type ?: 'charge', $data);
    }
}
