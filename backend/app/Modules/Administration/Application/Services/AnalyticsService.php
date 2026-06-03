<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Services;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Orders\Domain\Enums\OrderStatus;
use App\Modules\Orders\Domain\Models\Cart;
use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Models\Payment;
use Illuminate\Support\Carbon;

/**
 * Computes the enterprise dashboard metrics: sales, orders, production,
 * conversion and customers.
 */
final class AnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? Carbon::now()->subDays(30)->startOfDay();
        $to = $to ?? Carbon::now()->endOfDay();

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'sales' => $this->sales($from, $to),
            'orders' => $this->orders($from, $to),
            'production' => $this->production(),
            'conversion' => $this->conversion($from, $to),
            'customers' => $this->customers($from, $to),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sales(Carbon $from, Carbon $to): array
    {
        $paid = Payment::query()
            ->where('status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$from, $to]);

        return [
            'revenue' => (int) (clone $paid)->sum('amount'),
            'paid_payments' => (clone $paid)->count(),
            'currency' => 'USD',
            'average_order_value' => $this->averageOrderValue($from, $to),
        ];
    }

    private function averageOrderValue(Carbon $from, Carbon $to): int
    {
        $query = Order::query()->whereBetween('placed_at', [$from, $to]);
        $count = (clone $query)->count();

        return $count === 0 ? 0 : (int) round((int) (clone $query)->sum('total_amount') / $count);
    }

    /**
     * @return array<string, mixed>
     */
    private function orders(Carbon $from, Carbon $to): array
    {
        $byStatus = [];
        foreach (OrderStatus::cases() as $status) {
            $byStatus[$status->value] = Order::query()
                ->where('status', $status->value)
                ->whereBetween('placed_at', [$from, $to])
                ->count();
        }

        return [
            'total' => Order::query()->whereBetween('placed_at', [$from, $to])->count(),
            'by_status' => $byStatus,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function production(): array
    {
        return [
            'in_production' => Order::query()->where('status', OrderStatus::InProduction->value)->count(),
            'ready' => Order::query()->where('status', OrderStatus::Ready->value)->count(),
            'upcoming_pickups' => Order::query()
                ->whereNotNull('pickup_date')
                ->whereBetween('pickup_date', [Carbon::now()->startOfDay(), Carbon::now()->addDays(7)->endOfDay()])
                ->whereNotIn('status', [OrderStatus::Completed->value, OrderStatus::Cancelled->value])
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function conversion(Carbon $from, Carbon $to): array
    {
        $carts = Cart::query()->count();
        $orders = Order::query()->whereBetween('placed_at', [$from, $to])->count();
        $paidOrders = Order::query()
            ->whereIn('status', [
                OrderStatus::Confirmed->value, OrderStatus::InProduction->value,
                OrderStatus::Ready->value, OrderStatus::Completed->value,
            ])
            ->whereBetween('placed_at', [$from, $to])
            ->count();

        return [
            'carts' => $carts,
            'orders' => $orders,
            'cart_to_order_rate' => $carts === 0 ? 0.0 : round($orders / $carts, 4),
            'order_to_paid_rate' => $orders === 0 ? 0.0 : round($paidOrders / $orders, 4),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function customers(Carbon $from, Carbon $to): array
    {
        return [
            'total' => User::query()->count(),
            'new' => User::query()->whereBetween('created_at', [$from, $to])->count(),
            'with_orders' => Order::query()->distinct('user_id')->count('user_id'),
        ];
    }
}
