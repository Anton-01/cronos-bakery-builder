<?php

declare(strict_types=1);

namespace App\Modules\Orders\Application\Services;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Orders\Domain\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Read access to a customer's order history and detail.
 */
final class OrderService
{
    /**
     * @return LengthAwarePaginator<Order>
     */
    public function history(User $user): LengthAwarePaginator
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->with(['items', 'branch'])
            ->orderByDesc('placed_at')
            ->paginate(10);
    }

    public function get(User $user, string $id): Order
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->with(['items', 'branch'])
            ->whereKey($id)
            ->firstOr(fn () => throw new NotFoundHttpException('Order not found.'));
    }
}
