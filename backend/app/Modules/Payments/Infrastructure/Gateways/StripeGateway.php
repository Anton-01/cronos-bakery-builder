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
 * Stripe strategy. Webhook verification follows Stripe's scheme:
 * `Stripe-Signature: t=<ts>,v1=<hmacSHA256(secret, "<ts>.<body>")>`.
 *
 * In production the createCharge() body would call the Stripe SDK to create a
 * PaymentIntent; here it returns a sandbox-friendly reference + client secret.
 */
class StripeGateway extends AbstractGateway
{
    public function type(): GatewayType
    {
        return GatewayType::Stripe;
    }

    public function createCharge(ChargeRequest $request, GatewayConfig $config): ChargeResult
    {
        $reference = 'pi_' . Str::random(24);

        return new ChargeResult(
            reference: $reference,
            status: PaymentStatus::Pending,
            checkout: [
                'type' => 'client_secret',
                'client_secret' => $reference . '_secret_' . Str::random(16),
                'publishable_key' => $config->credential('public_key'),
            ],
            raw: ['intent' => $reference, 'amount' => $request->amount],
        );
    }

    public function verifySignature(string $payload, array $headers, GatewayConfig $config): bool
    {
        $header = $headers['stripe-signature'] ?? '';
        parse_str(str_replace(',', '&', $header), $parts);

        $timestamp = $parts['t'] ?? '';
        $given = $parts['v1'] ?? '';
        $expected = $this->hmac($timestamp . '.' . $payload, $config->webhookSecret());

        return $this->secureEquals($expected, (string) $given);
    }

    public function parseWebhook(string $payload): WebhookEvent
    {
        $data = $this->decode($payload);
        $type = (string) ($data['type'] ?? '');
        $reference = (string) ($data['data']['object']['id'] ?? '');

        $status = match ($type) {
            'payment_intent.succeeded' => PaymentStatus::Paid,
            'payment_intent.payment_failed' => PaymentStatus::Failed,
            'charge.refunded' => PaymentStatus::Refunded,
            'payment_intent.canceled' => PaymentStatus::Cancelled,
            default => PaymentStatus::Processing,
        };

        return new WebhookEvent($reference, $status, $type, $data);
    }
}
