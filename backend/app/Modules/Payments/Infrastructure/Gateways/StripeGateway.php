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
 * Stripe strategy. Webhook verification follows Stripe's scheme:
 * `Stripe-Signature: t=<ts>,v1=<hmacSHA256(secret, "<ts>.<body>")>`.
 *
 * In production every call goes to the Stripe REST API through
 * {@see AbstractGateway::callProvider()} (timeouts, retries, typed errors).
 * In sandbox the charge/refund are simulated so local environments work
 * without credentials or network access.
 */
class StripeGateway extends AbstractGateway
{
    private const API = 'https://api.stripe.com/v1';

    public function driver(): string
    {
        return 'stripe';
    }

    public function processPayment(ChargeRequest $request): ChargeResult
    {
        if (! $this->isProduction()) {
            $reference = 'pi_' . Str::random(24);

            return new ChargeResult(
                reference: $reference,
                status: PaymentStatus::Pending,
                checkout: [
                    'type' => 'client_secret',
                    'client_secret' => $reference . '_secret_' . Str::random(16),
                    'publishable_key' => $this->credential('public_key'),
                ],
                raw: ['intent' => $reference, 'amount' => $request->amount, 'simulated' => true],
            );
        }

        $response = $this->callProvider(
            fn (PendingRequest $http) => $http
                ->withToken((string) $this->credential('secret_key'))
                ->withHeaders(['Idempotency-Key' => $request->idempotencyKey])
                ->asForm()
                ->post(self::API . '/payment_intents', [
                    'amount' => $request->amount,
                    'currency' => strtolower($request->currency),
                    'description' => 'Order ' . $request->orderNumber,
                    'receipt_email' => $request->customerEmail,
                    'automatic_payment_methods[enabled]' => 'true',
                ]),
        );

        $data = $response->json();

        return new ChargeResult(
            reference: (string) $data['id'],
            status: $this->mapStatus((string) ($data['status'] ?? 'pending')),
            checkout: [
                'type' => 'client_secret',
                'client_secret' => (string) ($data['client_secret'] ?? ''),
                'publishable_key' => $this->credential('public_key'),
            ],
            raw: (array) $data,
        );
    }

    public function refund(Transaction $transaction, ?int $amount = null): RefundResult
    {
        $amount ??= $transaction->amount;

        if (! $this->isProduction()) {
            return new RefundResult(
                providerRefundId: 're_' . Str::random(24),
                status: PaymentStatus::Refunded,
                amount: $amount,
                raw: ['simulated' => true],
            );
        }

        $response = $this->callProvider(
            fn (PendingRequest $http) => $http
                ->withToken((string) $this->credential('secret_key'))
                ->asForm()
                ->post(self::API . '/refunds', [
                    'payment_intent' => $transaction->provider_transaction_id,
                    'amount' => $amount,
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
        $header = $headers['stripe-signature'] ?? '';
        parse_str(str_replace(',', '&', $header), $parts);

        $timestamp = $parts['t'] ?? '';
        $given = $parts['v1'] ?? '';
        $expected = $this->hmac($timestamp . '.' . $payload, $this->config()->webhookSecret());

        return $this->secureEquals($expected, (string) $given);
    }

    protected function parseWebhook(string $payload): WebhookEvent
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

        return new WebhookEvent(
            providerEventId: (string) ($data['id'] ?? '') ?: $this->fallbackEventId($payload),
            reference: $reference,
            status: $status,
            eventType: $type,
            raw: $data,
        );
    }
}
