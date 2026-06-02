<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Controllers;

use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Application\Services\PaymentService;
use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Domain\Models\GatewayConfig;
use App\Modules\Payments\Domain\Models\Payment;
use App\Modules\Payments\Presentation\Http\Requests\InitiatePaymentRequest;
use App\Modules\Payments\Presentation\Http\Resources\PaymentResource;
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
        $gateways = GatewayConfig::query()->where('is_active', true)->orderBy('gateway')->get()
            ->map(fn (GatewayConfig $config) => [
                'gateway' => $config->gateway->value,
                'label' => $config->gateway->label(),
                'mode' => $config->mode->value,
            ])->values();

        return response()->json(['data' => $gateways]);
    }

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $order = Order::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($request->validated('order_id'))
            ->firstOr(fn () => throw new NotFoundHttpException('Order not found.'));

        $result = $this->payments->initiate($order, GatewayType::from($request->validated('gateway')));

        return (new PaymentResource($result['payment']))
            ->additional(['checkout' => $result['checkout']])
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function show(Request $request, string $payment): PaymentResource
    {
        $model = Payment::query()
            ->where('user_id', $request->user()->id)
            ->with('events')
            ->whereKey($payment)
            ->firstOr(fn () => throw new NotFoundHttpException('Payment not found.'));

        return new PaymentResource($model);
    }
}
