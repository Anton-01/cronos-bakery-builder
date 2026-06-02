<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Application\Services;

use App\Modules\Notifications\Domain\Enums\NotificationEvent;
use App\Modules\Notifications\Domain\Events\AutomationTriggered;
use App\Modules\Notifications\Domain\Models\ReminderRule;
use App\Modules\Orders\Domain\Enums\FulfillmentType;
use App\Modules\Orders\Domain\Enums\OrderStatus;
use App\Modules\Orders\Domain\Models\Order;
use Illuminate\Support\Carbon;

/**
 * Fires reminder automations for upcoming pickups, N hours before the scheduled
 * time, for every active reminder rule. Idempotent via per-order/offset keys.
 */
final class ReminderService
{
    private const ACTIVE_STATUSES = [
        OrderStatus::Pending,
        OrderStatus::Confirmed,
        OrderStatus::InProduction,
        OrderStatus::Ready,
    ];

    /**
     * @return int number of reminders triggered
     */
    public function dispatchDue(?Carbon $now = null): int
    {
        $now = $now ?? Carbon::now();
        $rules = ReminderRule::query()->where('is_active', true)->get();

        if ($rules->isEmpty()) {
            return 0;
        }

        $maxOffset = (int) $rules->max('offset_hours');

        $orders = Order::query()
            ->with('user')
            ->where('fulfillment_type', FulfillmentType::Pickup->value)
            ->whereIn('status', array_map(fn (OrderStatus $s) => $s->value, self::ACTIVE_STATUSES))
            ->whereNotNull('pickup_date')
            ->whereNotNull('pickup_time')
            ->whereBetween('pickup_date', [
                $now->copy()->startOfDay(),
                $now->copy()->addHours($maxOffset)->endOfDay(),
            ])
            ->get();

        $count = 0;

        foreach ($orders as $order) {
            $target = $order->pickup_date->copy()->setTimeFromTimeString($order->pickup_time);

            foreach ($rules as $rule) {
                if (! $this->isDue($now, $target, $rule->offset_hours)) {
                    continue;
                }

                AutomationTriggered::dispatch(
                    NotificationEvent::OrderReminder->value,
                    [
                        'customer_name' => $order->user?->name ?? 'cliente',
                        'order_number' => $order->number,
                        'pickup_date' => $target->toDateString(),
                        'pickup_time' => $order->pickup_time,
                        'hours' => $rule->offset_hours,
                    ],
                    $order->user?->email ?? '',
                    "order.reminder:{$order->id}:{$rule->offset_hours}",
                );

                $count++;
            }
        }

        return $count;
    }

    /**
     * Due when the target is within the (offset-1h, offset] window from now.
     */
    private function isDue(Carbon $now, Carbon $target, int $offsetHours): bool
    {
        if ($target->lessThanOrEqualTo($now)) {
            return false;
        }

        $upper = $now->copy()->addHours($offsetHours);
        $lower = $now->copy()->addHours(max(0, $offsetHours - 1));

        return $target->greaterThan($lower) && $target->lessThanOrEqualTo($upper);
    }
}
