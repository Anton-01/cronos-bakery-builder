<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Controllers\Admin;

use App\Modules\Payments\Application\Services\PaymentGatewayManager;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use App\Modules\Payments\Presentation\Http\Requests\StorePaymentGatewayRequest;
use App\Modules\Payments\Presentation\Http\Requests\UpdatePaymentGatewayRequest;
use App\Modules\Payments\Presentation\Http\Resources\PaymentGatewayResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * Admin CRUD for gateway instances. Brand isolation follows the CMS
 * convention: an optional `brand_id` query parameter scopes the listing.
 * No provider conditionals here — everything is resolved by the manager.
 */
class PaymentGatewayController extends Controller
{
    public function __construct(private readonly PaymentGatewayManager $manager)
    {
    }

    /**
     * Driver metadata (labels + dynamic credential field definitions) so the
     * frontend renders configuration forms without hardcoding providers.
     */
    public function drivers(): JsonResponse
    {
        return response()->json(['data' => $this->manager->supportedDrivers()]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $brandId = $request->filled('brand_id') ? (int) $request->query('brand_id') : null;

        $gateways = PaymentGateway::query()
            ->forBrand($brandId)
            ->orderBy('driver_name')
            ->orderBy('name')
            ->get();

        return PaymentGatewayResource::collection($gateways);
    }

    public function store(StorePaymentGatewayRequest $request): JsonResponse
    {
        $gateway = PaymentGateway::create($request->validated());

        return (new PaymentGatewayResource($gateway))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(UpdatePaymentGatewayRequest $request, PaymentGateway $gateway): PaymentGatewayResource
    {
        $data = $request->validated();

        // Merge semantics for credentials: provided non-empty values overwrite,
        // explicit nulls remove the key, omitted keys are preserved. The
        // frontend sends only re-typed fields, never the masked hints.
        if (array_key_exists('credentials', $data)) {
            $current = $gateway->credentials ?? [];
            foreach ((array) $data['credentials'] as $key => $value) {
                if ($value === null || $value === '') {
                    unset($current[$key]);
                } else {
                    $current[$key] = $value;
                }
            }
            $data['credentials'] = $current === [] ? null : $current;
        }

        $gateway->update($data);

        return new PaymentGatewayResource($gateway->refresh());
    }

    public function destroy(PaymentGateway $gateway): JsonResponse
    {
        $gateway->delete(); // soft delete — history in transactions stays intact

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
