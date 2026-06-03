<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Domain\Services;

use App\Modules\ProductBuilder\Domain\Models\Product;

/**
 * Computes the dynamic price of a configured product. Only visible options
 * contribute; each selected value applies its price modifier (add/subtract/set)
 * to the running total, processed in option then value order.
 */
final class PriceCalculator
{
    /**
     * @param  array<string, array<int, string>>  $selections  option key => value(s)
     * @param  array<int, string>  $visibleKeys
     * @return array{base: int, total: int, currency: string, items: array<int, array<string, mixed>>}
     */
    public function calculate(Product $product, array $selections, array $visibleKeys): array
    {
        $total = $product->base_price_amount;
        $items = [];

        foreach ($product->options as $option) {
            if (! in_array($option->key, $visibleKeys, true)) {
                continue;
            }

            if (! $option->type->hasValues()) {
                continue; // text/textarea carry no price
            }

            $selected = $selections[$option->key] ?? [];

            foreach ($option->values as $value) {
                if (! in_array($value->value, $selected, true)) {
                    continue;
                }

                $before = $total;
                $total = $value->price_modifier_type->apply($total, $value->price_modifier_amount);

                $items[] = [
                    'option' => $option->key,
                    'value' => $value->value,
                    'label' => $value->label,
                    'modifier' => $value->price_modifier_type->value,
                    'amount' => $value->price_modifier_amount,
                    'delta' => $total - $before,
                ];
            }
        }

        return [
            'base' => $product->base_price_amount,
            'total' => max(0, $total),
            'currency' => $product->currency,
            'items' => $items,
        ];
    }
}
