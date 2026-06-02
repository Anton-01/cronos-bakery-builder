<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Services;

use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Application\DTO\ChargeRequest;
use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Domain\Models\GatewayConfig;
use App\Modules\Payments\Domain\Models\Payment;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Initiates payments through the configured gateway (Strategy), persisting a
 * pending Payment plus a traceable "created" event.
 */
final class PaymentService
{
    public function __construct(private readonly PaymentGatewayManager $gateways)
    {
    }

    /**
     * @return array{payment: Payment, checkout: array<string, mixed>}
     *
     * @throws ValidationException when the gateway is not active.
     */
    public function initiate(Order $order, GatewayType $gateway): array
    {
        $config = GatewayConfig::query()
            ->where('gateway', $gateway->value)
            ->where('is_active', true)
            ->first();

        if ($config === null) {
            throw ValidationException::withMessages([
                'gateway' => ["The {$gateway->label()} gateway is not available."],
            ]);
        }

        $strategy = $this->gateways->for($gateway);
        $idempotencyKey = (string) Str::uuid();

        $result = $strategy->createCharge(new ChargeRequest(
            amount: $order->total_amount,
            currency: $order->currency,
            orderNumber: $order->number,
            idempotencyKey: $idempotencyKey,
            customerEmail: $order->user?->email,
        ), $config);

        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => $gateway->value,
            'mode' => $config->mode->value,
            'status' => $result->status->value,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'reference' => $result->reference,
            'idempotency_key' => $idempotencyKey,
            'metadata' => $result->checkout,
        ]);

        $payment->events()->create([
            'type' => 'created',
            'status' => $result->status->value,
            'payload' => $result->raw,
        ]);

        return ['payment' => $payment, 'checkout' => $result->checkout];
    }
}
