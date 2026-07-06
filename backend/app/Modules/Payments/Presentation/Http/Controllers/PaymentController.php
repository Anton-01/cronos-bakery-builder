<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Controllers;

use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Application\Services\PaymentService;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use App\Modules\Payments\Domain\Models\Transaction;
use App\Modules\Payments\Presentation\Http\Requests\InitiatePaymentRequest;
use App\Modules\Payments\Presentation\Http\Resources\TransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    /**
     * Active gateways available to the customer at checkout (no secrets).
     */
    public function gateways(): JsonResponse
    {
        $gateways = PaymentGateway::query()
            ->active()
            ->orderBy('driver_name')
            ->get()
            ->unique('driver_name')
            ->map(fn (PaymentGateway $gateway) => [
                'gateway' => $gateway->driver_name,
                'label' => $gateway->driverLabel(),
                'environment' => $gateway->environment->value,
            ])
            ->values();

        return response()->json(['data' => $gateways]);
    }

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $order = Order::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($request->validated('order_id'))
            ->firstOr(fn () => throw new NotFoundHttpException('Order not found.'));

        $result = $this->payments->initiate($order, $request->validated('gateway'));

        return (new TransactionResource($result['transaction']))
            ->additional(['checkout' => $result['checkout']])
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $transaction): TransactionResource
    {
        $model = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->with('events')
            ->whereKey($transaction)
            ->firstOr(fn () => throw new NotFoundHttpException('Transaction not found.'));

        return new TransactionResource($model);
    }
}
