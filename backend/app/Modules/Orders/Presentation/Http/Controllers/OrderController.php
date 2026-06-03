<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Modules\Orders\Application\Services\OrderService;
use App\Modules\Orders\Presentation\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orders->history($request->user()));
    }

    public function show(Request $request, string $order): OrderResource
    {
        return new OrderResource($this->orders->get($request->user(), $order));
    }
}
