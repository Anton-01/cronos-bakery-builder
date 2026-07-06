<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Contracts;

use App\Modules\Payments\Application\DTO\ChargeRequest;
use App\Modules\Payments\Application\DTO\ChargeResult;
use App\Modules\Payments\Application\DTO\RefundResult;
use App\Modules\Payments\Application\DTO\WebhookEvent;
use App\Modules\Payments\Domain\Exceptions\GatewayException;
use App\Modules\Payments\Domain\Exceptions\InvalidWebhookSignatureException;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use App\Modules\Payments\Domain\Models\Transaction;

/**
 * Strategy contract every payment provider implements. The application layer
 * depends only on this interface, so providers are fully interchangeable and
 * resolved dynamically by the PaymentGatewayManager from `driver_name` —
 * no if/else chains anywhere.
 */
interface PaymentGatewayInterface
{
    /**
     * The driver key this strategy serves (must match config/payments.php).
     */
    public function driver(): string;

    /**
     * Bind the strategy to a configured gateway instance (credentials +
     * environment). Must be called before any other operation.
     */
    public function initialize(PaymentGateway $gateway): static;

    /**
     * Create a charge/intent with the provider and return the actionable result.
     *
     * @throws GatewayException on provider errors (incl. timeout / rate limit subtypes).
     */
    public function processPayment(ChargeRequest $request): ChargeResult;

    /**
     * Verify the webhook's cryptographic signature and translate the payload
     * into a normalised event. MUST throw before parsing state changes when
     * the signature does not match.
     *
     * @param  array<string, string>  $headers
     *
     * @throws InvalidWebhookSignatureException when the signature is invalid.
     */
    public function handleWebhook(string $payload, array $headers): WebhookEvent;

    /**
     * Refund a (partially) captured transaction with the provider.
     *
     * @param  int|null  $amount  minor units; null = full refund.
     *
     * @throws GatewayException on provider errors.
     */
    public function refund(Transaction $transaction, ?int $amount = null): RefundResult;
}
