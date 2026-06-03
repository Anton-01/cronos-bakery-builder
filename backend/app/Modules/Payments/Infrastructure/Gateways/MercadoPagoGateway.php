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
 * MercadoPago strategy. Creates a checkout preference and redirects the buyer
 * to its init_point. Webhooks are authenticated via the `x-signature` HMAC of
 * the request body using the configured secret.
 */
class MercadoPagoGateway extends AbstractGateway
{
    public function type(): GatewayType
    {
        return GatewayType::MercadoPago;
    }

    public function createCharge(ChargeRequest $request, GatewayConfig $config): ChargeResult
    {
        $reference = 'pref_' . Str::random(20);
        $base = $config->mode->value === 'production'
            ? 'https://www.mercadopago.com/checkout/v1/redirect'
            : 'https://sandbox.mercadopago.com/checkout/v1/redirect';

        return new ChargeResult(
            reference: $reference,
            status: PaymentStatus::Pending,
            checkout: [
                'type' => 'redirect',
                'redirect_url' => $base . '?pref_id=' . $reference,
            ],
            raw: ['preference_id' => $reference],
        );
    }

    public function verifySignature(string $payload, array $headers, GatewayConfig $config): bool
    {
        $header = $headers['x-signature'] ?? '';
        parse_str(str_replace(',', '&', $header), $parts);
        $given = $parts['v1'] ?? $header;

        $expected = $this->hmac($payload, $config->webhookSecret());

        return $this->secureEquals($expected, (string) $given);
    }

    public function parseWebhook(string $payload): WebhookEvent
    {
        $data = $this->decode($payload);
        $reference = (string) ($data['data']['preference_id'] ?? $data['data']['id'] ?? '');
        $status = $this->mapStatus((string) ($data['data']['status'] ?? $data['action'] ?? ''));

        return new WebhookEvent($reference, $status, (string) ($data['type'] ?? 'payment'), $data);
    }
}
