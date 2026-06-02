<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Services;

use App\Modules\Notifications\Domain\Events\AutomationTriggered;
use App\Modules\Orders\Domain\Enums\OrderStatus;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Applies authoritative payment status changes and reconciles the related
 * order, recording every transition for traceability.
 */
final class ReconciliationService
{
    /**
     * Apply a new status to a payment (idempotently) and reconcile the order.
     *
     * @param  array<string, mixed>  $payload
     */
    public function applyStatus(Payment $payment, PaymentStatus $status, string $type, array $payload = []): Payment
    {
        return DB::transaction(function () use ($payment, $status, $type, $payload): Payment {
            $changed = $payment->status !== $status;

            if ($changed) {
                $payment->status = $status;
                if ($status === PaymentStatus::Paid) {
                    $payment->paid_at = now();
                }
                $payment->save();

                $payment->events()->create([
                    'type' => 'status_change',
                    'status' => $status->value,
                    'payload' => $payload,
                ]);

                $this->reconcileOrder($payment, $status);
            }

            $payment->events()->create([
                'type' => $type,
                'status' => $status->value,
                'payload' => $payload,
            ]);

            return $payment->refresh();
        });
    }

    /**
     * Record a retry attempt against a still-pending payment.
     */
    public function retry(Payment $payment): Payment
    {
        $payment->increment('attempts');
        $payment->events()->create([
            'type' => 'retry',
            'status' => $payment->status->value,
            'payload' => ['attempt' => $payment->attempts],
        ]);

        return $payment->refresh();
    }

    private function reconcileOrder(Payment $payment, PaymentStatus $status): void
    {
        $order = $payment->order;
        if ($order === null) {
            return;
        }

        $order->status = match ($status) {
            PaymentStatus::Paid => OrderStatus::Confirmed,
            PaymentStatus::Cancelled, PaymentStatus::Failed => OrderStatus::Cancelled,
            default => $order->status,
        };
        $order->save();

        // Notify the customer when their payment is approved.
        if ($status === PaymentStatus::Paid && $order->user !== null) {
            AutomationTriggered::dispatch(
                'payment.approved',
                [
                    'customer_name' => $order->user->name,
                    'order_number' => $order->number,
                    'total' => number_format($order->total_amount / 100, 2),
                    'status' => $order->status->label(),
                ],
                (string) $order->user->email,
            );
        }
    }
}
