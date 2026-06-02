<?php

declare(strict_types=1);

namespace App\Modules\Orders\Application\Services;

use App\Modules\Authentication\Domain\Models\User;
use App\Modules\Orders\Domain\Models\Cart;
use App\Modules\Orders\Domain\Models\CartItem;
use App\Modules\ProductBuilder\Application\Services\ConfiguratorService;
use Illuminate\Validation\ValidationException;

/**
 * Manages a customer's persistent cart. Configured cakes are validated and
 * priced through the Product Builder's {@see ConfiguratorService}, and a full
 * configuration snapshot is stored so the cart survives price/config changes.
 */
final class CartService
{
    public function __construct(private readonly ConfiguratorService $configurator)
    {
    }

    public function forUser(User $user): Cart
    {
        /** @var Cart $cart */
        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
        $cart->load('items');

        return $cart;
    }

    /**
     * Add a configured product to the cart (server-validated + priced).
     *
     * @param  array<string, mixed>  $selections
     *
     * @throws ValidationException
     */
    public function addItem(User $user, string $slug, array $selections, int $quantity): CartItem
    {
        $quote = $this->configurator->quote($slug, $selections);
        $product = $quote['product'];

        $cart = $this->forUser($user);

        $item = $cart->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'configuration' => [
                'selections' => $quote['selections'],
                'visible' => $quote['visible'],
                'price' => $quote['price'],
            ],
            'unit_price_amount' => $quote['price']['total'],
            'currency' => $quote['price']['currency'],
            'quantity' => $quantity,
        ]);

        return $item;
    }

    public function updateQuantity(User $user, string $itemId, int $quantity): CartItem
    {
        $item = $this->findItem($user, $itemId);
        $item->update(['quantity' => $quantity]);

        return $item->refresh();
    }

    public function removeItem(User $user, string $itemId): void
    {
        $this->findItem($user, $itemId)->delete();
    }

    public function clear(User $user): void
    {
        $this->forUser($user)->items()->delete();
    }

    private function findItem(User $user, string $itemId): CartItem
    {
        return CartItem::query()
            ->whereKey($itemId)
            ->whereHas('cart', fn ($q) => $q->where('user_id', $user->id))
            ->firstOrFail();
    }
}
