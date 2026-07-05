<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Controllers\Admin;

use App\Modules\Payments\Application\Services\PaymentGatewayManager;
use App\Modules\Payments\Application\Services\ReconciliationService;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\Transaction;
use App\Modules\Payments\Infrastructure\Jobs\RetryPaymentStatusJob;
use App\Modules\Payments\Presentation\Http\Resources\TransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

/**
 * Admin transaction centre: filterable history, detail with audit trail,
 * queued reconciliation retries and refunds through the gateway strategy.
 */
class TransactionController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly ReconciliationService $reconciliation,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'brand_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'gateway_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $transactions = Transaction::query()
            ->with(['gateway', 'order'])
            ->forBrand(isset($filters['brand_id']) ? (int) $filters['brand_id'] : null)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['gateway_id'] ?? null, fn ($q, $id) => $q->where('payment_gateway_id', (int) $id))
            ->when($filters['date_from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return TransactionResource::collection($transactions);
    }

    public function show(Transaction $transaction): TransactionResource
    {
        return new TransactionResource($transaction->load(['gateway', 'order', 'events']));
    }

    /**
     * Refund through the gateway strategy (resolved dynamically from the
     * transaction's gateway driver_name — no conditionals).
     */
    public function refund(Transaction $transaction): TransactionResource|JsonResponse
    {
        if ($transaction->status !== PaymentStatus::Paid) {
            return response()->json(
                ['message' => 'Only paid transactions can be refunded.'],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $strategy = $this->gateways->forGateway($transaction->gateway);
        $refund = $strategy->refund($transaction);

        $this->reconciliation->applyRefund($transaction, $refund);

        return new TransactionResource($transaction->refresh()->load(['gateway', 'order', 'events']));
    }

    public function retry(Transaction $transaction): JsonResponse
    {
        RetryPaymentStatusJob::dispatch($transaction->id);

        return response()->json(['message' => 'Reconciliation retry queued.']);
    }
}
