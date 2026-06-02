<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Services;

use App\Modules\Payments\Domain\Models\GatewayConfig;
use App\Modules\Payments\Domain\Models\Payment;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles inbound gateway webhooks: verifies the signature, records the event
 * for traceability and reconciles the payment + order.
 */
final class WebhookService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly ReconciliationService $reconciliation,
    ) {
    }

    /**
     * @param  array<string, string>  $headers
     * @return array{handled: bool, status: string}
     *
     * @throws RuntimeException on invalid signature.
     */
    public function handle(string $gateway, string $payload, array $headers): array
    {
        $strategy = $this->gateways->fromString($gateway);

        $config = GatewayConfig::query()->where('gateway', $gateway)->first();
        if ($config === null) {
            throw new NotFoundHttpException('Gateway not configured.');
        }

        $event = $strategy->parseWebhook($payload);

        $payment = Payment::query()
            ->where('gateway', $gateway)
            ->where('reference', $event->reference)
            ->first();

        // Unknown reference — acknowledge without processing (no record to trace).
        if ($payment === null) {
            return ['handled' => false, 'status' => 'ignored'];
        }

        $valid = $strategy->verifySignature($payload, $headers, $config);

        if (! $valid) {
            $payment->events()->create([
                'type' => 'webhook',
                'status' => $event->status->value,
                'signature_valid' => false,
                'payload' => $event->raw,
            ]);

            throw new RuntimeException('Invalid webhook signature.');
        }

        $payment->events()->create([
            'type' => 'webhook',
            'status' => $event->status->value,
            'signature_valid' => true,
            'payload' => $event->raw,
        ]);

        $this->reconciliation->applyStatus($payment, $event->status, 'reconciled', $event->raw);

        return ['handled' => true, 'status' => $event->status->value];
    }
}
