<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Controllers\Admin;

use App\Modules\Notifications\Domain\Events\AutomationTriggered;
use App\Modules\Orders\Domain\Enums\OrderStatus;
use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Orders\Presentation\Http\Requests\UpdateOrderStatusRequest;
use App\Modules\Orders\Presentation\Http\Resources\OrderResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * Admin order management: list orders and transition their status, firing the
 * matching automation events (production started / order ready).
 */
class OrderAdminController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return OrderResource::collection(
            Order::query()->with(['items', 'branch'])->latest('placed_at')->paginate(20),
        );
    }

    public function updateStatus(UpdateOrderStatusRequest $request, string $order): OrderResource
    {
        /** @var Order $model */
        $model = Order::query()->with('user')->findOrFail($order);
        $status = OrderStatus::from($request->validated('status'));

        $model->update(['status' => $status->value]);

        $event = match ($status) {
            OrderStatus::InProduction => 'production.started',
            OrderStatus::Ready => 'order.ready',
            default => null,
        };

        if ($event !== null && $model->user !== null) {
            AutomationTriggered::dispatch(
                $event,
                [
                    'customer_name' => $model->user->name,
                    'order_number' => $model->number,
                    'total' => number_format($model->total_amount / 100, 2),
                    'status' => $status->label(),
                ],
                (string) $model->user->email,
            );
        }

        return new OrderResource($model->refresh()->load(['items', 'branch']));
    }
}
