<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Gateways;

use App\Modules\Payments\Application\DTO\ChargeRequest;
use App\Modules\Payments\Application\DTO\ChargeResult;
use App\Modules\Payments\Application\DTO\RefundResult;
use App\Modules\Payments\Application\DTO\WebhookEvent;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\Transaction;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;

/**
 * MercadoPago strategy. Creates a checkout preference and redirects the buyer
 * to its init_point. Webhooks are authenticated via the `x-signature` HMAC of
 * the request body using the configured secret.
 */
class MercadoPagoGateway extends AbstractGateway
{
    private const API = 'https://api.mercadopago.com';

    public function driver(): string
    {
        return 'mercadopago';
    }

    public function processPayment(ChargeRequest $request): ChargeResult
    {
        if (! $this->isProduction()) {
            $reference = 'pref_' . Str::random(20);

            return new ChargeResult(
                reference: $reference,
                status: PaymentStatus::Pending,
                checkout: [
                    'type' => 'redirect',
                    'redirect_url' => 'https://sandbox.mercadopago.com/checkout/v1/redirect?pref_id=' . $reference,
                ],
                raw: ['preference_id' => $reference, 'simulated' => true],
            );
        }

        $response = $this->callProvider(
            fn (PendingRequest $http) => $http
                ->withToken((string) $this->credential('access_token'))
                ->withHeaders(['X-Idempotency-Key' => $request->idempotencyKey])
                ->post(self::API . '/checkout/preferences', [
                    'external_reference' => $request->orderNumber,
                    'items' => [[
                        'title' => 'Order ' . $request->orderNumber,
                        'quantity' => 1,
                        'currency_id' => $request->currency,
                        'unit_price' => round($request->amount / 100, 2),
                    ]],
                    'payer' => ['email' => $request->customerEmail],
                    'back_urls' => ['success' => $request->returnUrl],
                ]),
        );

        $data = $response->json();

        return new ChargeResult(
            reference: (string) ($data['id'] ?? ''),
            status: PaymentStatus::Pending,
            checkout: [
                'type' => 'redirect',
                'redirect_url' => (string) ($data['init_point'] ?? ''),
            ],
            raw: (array) $data,
        );
    }

    public function refund(Transaction $transaction, ?int $amount = null): RefundResult
    {
        $amount ??= $transaction->amount;

        if (! $this->isProduction()) {
            return new RefundResult(
                providerRefundId: 'mpref_' . Str::random(16),
                status: PaymentStatus::Refunded,
                amount: $amount,
                raw: ['simulated' => true],
            );
        }

        $response = $this->callProvider(
            fn (PendingRequest $http) => $http
                ->withToken((string) $this->credential('access_token'))
                ->post(self::API . "/v1/payments/{$transaction->provider_transaction_id}/refunds", [
                    'amount' => round($amount / 100, 2),
                ]),
        );

        $data = $response->json();

        return new RefundResult(
            providerRefundId: (string) ($data['id'] ?? ''),
            status: PaymentStatus::Refunded,
            amount: $amount,
            raw: (array) $data,
        );
    }

    protected function verifySignature(string $payload, array $headers): bool
    {
        $header = $headers['x-signature'] ?? '';
        parse_str(str_replace(',', '&', $header), $parts);
        $given = $parts['v1'] ?? $header;

        $expected = $this->hmac($payload, $this->config()->webhookSecret());

        return $this->secureEquals($expected, (string) $given);
    }

    protected function parseWebhook(string $payload): WebhookEvent
    {
        $data = $this->decode($payload);
        $reference = (string) ($data['data']['preference_id'] ?? $data['data']['id'] ?? '');
        $status = $this->mapStatus((string) ($data['data']['status'] ?? $data['action'] ?? ''));

        return new WebhookEvent(
            providerEventId: (string) ($data['id'] ?? '') ?: $this->fallbackEventId($payload),
            reference: $reference,
            status: $status,
            eventType: (string) ($data['type'] ?? 'payment'),
            raw: $data,
        );
    }
}
