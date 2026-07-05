<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Services;

use App\Modules\Notifications\Domain\Events\AutomationTriggered;
use App\Modules\Orders\Domain\Enums\OrderStatus;
use App\Modules\Payments\Application\DTO\RefundResult;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Applies authoritative transaction status changes and reconciles the related
 * order, recording every transition for traceability.
 */
final class ReconciliationService
{
    /**
     * Apply a new status to a transaction (idempotently) and reconcile the order.
     *
     * @param  array<string, mixed>  $payload
     */
    public function applyStatus(Transaction $transaction, PaymentStatus $status, string $type, array $payload = []): Transaction
    {
        return DB::transaction(function () use ($transaction, $status, $type, $payload): Transaction {
            $changed = $transaction->status !== $status;

            if ($changed) {
                $transaction->status = $status;
                if ($status === PaymentStatus::Paid) {
                    $transaction->paid_at = now();
                }
                if ($payload !== []) {
                    $transaction->raw_response = $payload;
                }
                $transaction->save();

                $transaction->events()->create([
                    'type' => 'status_change',
                    'status' => $status->value,
                    'payload' => $payload,
                ]);

                $this->reconcileOrder($transaction, $status);
            }

            $transaction->events()->create([
                'type' => $type,
                'status' => $status->value,
                'payload' => $payload,
            ]);

            return $transaction->refresh();
        });
    }

    /**
     * Record a provider refund and move the transaction to Refunded.
     */
    public function applyRefund(Transaction $transaction, RefundResult $refund): Transaction
    {
        return $this->applyStatus($transaction, PaymentStatus::Refunded, 'refund', [
            'provider_refund_id' => $refund->providerRefundId,
            'amount' => $refund->amount,
            'raw' => $refund->raw,
        ]);
    }

    /**
     * Record a retry attempt against a still-pending transaction.
     */
    public function retry(Transaction $transaction): Transaction
    {
        $transaction->increment('attempts');
        $transaction->events()->create([
            'type' => 'retry',
            'status' => $transaction->status->value,
            'payload' => ['attempt' => $transaction->attempts],
        ]);

        return $transaction->refresh();
    }

    private function reconcileOrder(Transaction $transaction, PaymentStatus $status): void
    {
        $order = $transaction->order;
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
