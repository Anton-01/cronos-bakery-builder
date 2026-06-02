<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Contracts;

use App\Modules\Payments\Application\DTO\ChargeRequest;
use App\Modules\Payments\Application\DTO\ChargeResult;
use App\Modules\Payments\Application\DTO\WebhookEvent;
use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Domain\Models\GatewayConfig;

/**
 * Strategy contract every payment provider implements. The application layer
 * depends only on this interface, so gateways are fully interchangeable.
 */
interface PaymentGateway
{
    public function type(): GatewayType;

    /**
     * Create a charge/intent with the provider and return the actionable result.
     */
    public function createCharge(ChargeRequest $request, GatewayConfig $config): ChargeResult;

    /**
     * Verify the authenticity of an incoming webhook using the configured secret.
     *
     * @param  array<string, string>  $headers
     */
    public function verifySignature(string $payload, array $headers, GatewayConfig $config): bool;

    /**
     * Translate a raw webhook payload into a normalised event.
     */
    public function parseWebhook(string $payload): WebhookEvent;
}
