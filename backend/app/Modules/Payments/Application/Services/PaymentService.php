<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Services;

use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Application\DTO\ChargeRequest;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use App\Modules\Payments\Domain\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Initiates payments through the configured gateway (Strategy), persisting a
 * pending Transaction plus a traceable "created" event. The idempotency key
 * is unique per gateway, so a retried initiation can never double-charge.
 */
final class PaymentService
{
    public function __construct(private readonly PaymentGatewayManager $gateways)
    {
    }

    /**
     * @return array{transaction: Transaction, checkout: array<string, mixed>}
     *
     * @throws ValidationException when no active gateway exists for the driver.
     */
    public function initiate(Order $order, string $driverName): array
    {
        $gateway = PaymentGateway::query()
            ->active()
            ->where('driver_name', $driverName)
            ->orderBy('brand_id') // deterministic: shared (null) gateway first
            ->first();

        if ($gateway === null) {
            throw ValidationException::withMessages([
                'gateway' => ["The {$driverName} gateway is not available."],
            ]);
        }

        $strategy = $this->gateways->forGateway($gateway);
        $idempotencyKey = (string) Str::uuid();

        // Gateway exceptions (timeout / rate limit / provider error) bubble up
        // as typed exceptions with their own HTTP renderers (504 / 429 / 502).
        $result = $strategy->processPayment(new ChargeRequest(
            amount: $order->total_amount,
            currency: $order->currency,
            orderNumber: $order->number,
            idempotencyKey: $idempotencyKey,
            customerEmail: $order->user?->email,
        ));

        $transaction = Transaction::create([
            'brand_id' => $gateway->brand_id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'payment_gateway_id' => $gateway->id,
            'provider_transaction_id' => $result->reference,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'status' => $result->status->value,
            'raw_response' => $result->raw,
            'checkout' => $result->checkout,
            'idempotency_key' => $idempotencyKey,
        ]);

        $transaction->events()->create([
            'type' => 'created',
            'status' => $result->status->value,
            'payload' => $result->raw,
        ]);

        return ['transaction' => $transaction, 'checkout' => $result->checkout];
    }
}
