<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Jobs;

use App\Modules\Payments\Application\Services\ReconciliationService;
use App\Modules\Payments\Domain\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Re-checks a still-pending payment, recording a retry attempt. With a live
 * gateway this is where a status fetch (poll) would happen; the exponential
 * backoff between attempts is driven by the queue.
 */
class RetryPaymentStatusJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /**
     * Exponential backoff between attempts (seconds).
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function __construct(public readonly string $paymentId)
    {
    }

    public function handle(ReconciliationService $reconciliation): void
    {
        $payment = Payment::query()->find($this->paymentId);

        if ($payment === null || $payment->status->isFinal()) {
            return;
        }

        $reconciliation->retry($payment);
    }
}
