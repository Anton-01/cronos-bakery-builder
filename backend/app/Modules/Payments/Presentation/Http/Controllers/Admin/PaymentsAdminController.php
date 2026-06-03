<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Controllers\Admin;

use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Domain\Models\GatewayConfig;
use App\Modules\Payments\Domain\Models\Payment;
use App\Modules\Payments\Infrastructure\Jobs\RetryPaymentStatusJob;
use App\Modules\Payments\Presentation\Http\Requests\UpdateGatewayConfigRequest;
use App\Modules\Payments\Presentation\Http\Resources\GatewayConfigResource;
use App\Modules\Payments\Presentation\Http\Resources\PaymentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class PaymentsAdminController extends Controller
{
    public function gateways(): AnonymousResourceCollection
    {
        return GatewayConfigResource::collection(GatewayConfig::query()->orderBy('gateway')->get());
    }

    public function updateGateway(UpdateGatewayConfigRequest $request, string $gateway): GatewayConfigResource
    {
        $type = GatewayType::from($gateway);

        $config = GatewayConfig::query()->updateOrCreate(
            ['gateway' => $type->value],
            [
                'mode' => $request->validated('mode'),
                // Credentials are free-form per provider; take the raw map.
                'credentials' => (array) $request->input('credentials', []),
                'is_active' => (bool) $request->validated('is_active', false),
            ],
        );

        return new GatewayConfigResource($config);
    }

    public function payments(): AnonymousResourceCollection
    {
        return PaymentResource::collection(
            Payment::query()->latest()->paginate(20),
        );
    }

    public function retry(string $payment): JsonResponse
    {
        $model = Payment::query()->findOrFail($payment);

        RetryPaymentStatusJob::dispatch($model->id);

        return response()->json(['message' => 'Reconciliation retry queued.']);
    }
}
