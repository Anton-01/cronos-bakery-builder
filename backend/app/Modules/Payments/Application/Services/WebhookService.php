<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Services;

use App\Modules\Payments\Domain\Exceptions\InvalidWebhookSignatureException;
use App\Modules\Payments\Domain\Models\GatewayWebhookEvent;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use App\Modules\Payments\Domain\Models\Transaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * Handles inbound gateway webhooks with two enterprise guarantees:
 *
 * 1. AUTHENTICITY — the strategy verifies the payload's cryptographic
 *    signature BEFORE any state change (invalid signature ⇒ 400, no writes).
 * 2. IDEMPOTENCY — every provider event id is recorded in
 *    gateway_webhook_events under a unique constraint; duplicate deliveries
 *    are acknowledged but never reprocessed, even under concurrency.
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
     * @throws InvalidWebhookSignatureException
     */
    public function handle(PaymentGateway $gateway, string $payload, array $headers): array
    {
        $strategy = $this->gateways->forGateway($gateway);

        // Throws InvalidWebhookSignatureException (→ 400) before touching state.
        $event = $strategy->handleWebhook($payload, $headers);

        // Idempotency gate: the unique constraint makes concurrent duplicates lose.
        try {
            $ledger = GatewayWebhookEvent::query()->create([
                'payment_gateway_id' => $gateway->id,
                'provider_event_id' => $event->providerEventId,
                'event_type' => $event->eventType,
                'payload' => $event->raw,
            ]);
        } catch (UniqueConstraintViolationException) {
            Log::info('payments.webhook.duplicate', [
                'gateway_id' => $gateway->id,
                'event_id' => $event->providerEventId,
            ]);

            return ['handled' => false, 'status' => 'duplicate'];
        }

        $transaction = Transaction::query()
            ->where('payment_gateway_id', $gateway->id)
            ->where('provider_transaction_id', $event->reference)
            ->first();

        // Unknown reference — acknowledge without processing (no record to trace).
        if ($transaction === null) {
            $ledger->update(['processed_at' => now()]);

            return ['handled' => false, 'status' => 'ignored'];
        }

        $transaction->events()->create([
            'type' => 'webhook',
            'status' => $event->status->value,
            'signature_valid' => true,
            'payload' => $event->raw,
        ]);

        $this->reconciliation->applyStatus($transaction, $event->status, 'reconciled', $event->raw);

        $ledger->update(['processed_at' => now()]);

        return ['handled' => true, 'status' => $event->status->value];
    }
}
