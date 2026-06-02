<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Modules\Orders\Application\Services\CheckoutService;
use App\Modules\Orders\Presentation\Http\Requests\CheckoutRequest;
use App\Modules\Orders\Presentation\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkout)
    {
    }

    public function store(CheckoutRequest $request): JsonResponse
    {
        $order = $this->checkout->place($request->user(), $request->validated());

        return (new OrderResource($order))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }
}
