<?php

declare(strict_types=1);

namespace App\Modules\Orders\Application\Services;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Notifications\Domain\Events\AutomationTriggered;
use App\Modules\Orders\Domain\Enums\FulfillmentType;
use App\Modules\Orders\Domain\Enums\OrderStatus;
use App\Modules\Orders\Domain\Models\Branch;
use App\Modules\Orders\Domain\Models\Cart;
use App\Modules\Orders\Domain\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Turns a customer's cart into a placed order, snapshotting line items and
 * fulfillment details (delivery address or pickup branch + slot).
 */
final class CheckoutService
{
    public function __construct(
        private readonly CartService $carts,
        private readonly AddressService $addresses,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ValidationException
     */
    public function place(User $user, array $payload): Order
    {
        $cart = $this->carts->forUser($user);

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => ['Your cart is empty.']]);
        }

        $type = FulfillmentType::from($payload['fulfillment_type']);
        $fulfillment = $this->resolveFulfillment($user, $type, $payload);

        $order = DB::transaction(function () use ($user, $cart, $type, $fulfillment, $payload): Order {
            $subtotal = $cart->subtotalAmount();

            $order = Order::create([
                'number' => $this->generateNumber(),
                'user_id' => $user->id,
                'status' => OrderStatus::Pending->value,
                'fulfillment_type' => $type->value,
                'shipping_address' => $fulfillment['shipping_address'],
                'branch_id' => $fulfillment['branch_id'],
                'pickup_date' => $fulfillment['pickup_date'],
                'pickup_time' => $fulfillment['pickup_time'],
                'subtotal_amount' => $subtotal,
                'total_amount' => $subtotal,
                'currency' => $cart->items->first()->currency,
                'notes' => $payload['notes'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_slug' => $item->product_slug,
                    'configuration' => $item->configuration,
                    'unit_price_amount' => $item->unit_price_amount,
                    'quantity' => $item->quantity,
                    'line_total_amount' => $item->lineTotal(),
                ]);
            }

            $this->carts->clear($user);

            return $order->load(['items', 'branch']);
        });

        // Fire the "order placed" automation (decoupled, queued delivery).
        AutomationTriggered::dispatch(
            'order.placed',
            [
                'customer_name' => $user->name,
                'order_number' => $order->number,
                'total' => number_format($order->total_amount / 100, 2),
                'status' => $order->status->label(),
            ],
            (string) $user->email,
        );

        return $order;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{shipping_address: array<string,mixed>|null, branch_id: string|null, pickup_date: string|null, pickup_time: string|null}
     *
     * @throws ValidationException
     */
    private function resolveFulfillment(User $user, FulfillmentType $type, array $payload): array
    {
        if ($type === FulfillmentType::Delivery) {
            $address = $this->addresses->find($user, (string) $payload['address_id']);

            return [
                'shipping_address' => $address->toSnapshot(),
                'branch_id' => null,
                'pickup_date' => null,
                'pickup_time' => null,
            ];
        }

        $branch = Branch::query()->active()->whereKey($payload['branch_id'] ?? null)->first();

        if ($branch === null) {
            throw ValidationException::withMessages(['branch_id' => ['Selecciona una sucursal válida.']]);
        }

        return [
            'shipping_address' => null,
            'branch_id' => $branch->id,
            'pickup_date' => $payload['pickup_date'],
            'pickup_time' => $payload['pickup_time'],
        ];
    }

    private function generateNumber(): string
    {
        return 'CR-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
    }
}
