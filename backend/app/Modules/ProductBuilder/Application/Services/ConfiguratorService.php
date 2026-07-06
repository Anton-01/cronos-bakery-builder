<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Application\Services;

use App\Modules\ProductBuilder\Domain\Models\Product;
use App\Modules\ProductBuilder\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\ProductBuilder\Domain\Services\DependencyResolver;
use App\Modules\ProductBuilder\Domain\Services\PriceCalculator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public-facing configurator use-cases: load a product's configuration,
 * validate a set of selections, resolve which options are visible and compute
 * the dynamic price authoritatively on the server.
 */
final class ConfiguratorService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly DependencyResolver $resolver,
        private readonly PriceCalculator $calculator,
    ) {
    }

    public function configuration(string $slug, bool $includeDraft = false): Product
    {
        $product = $includeDraft
            ? $this->products->findConfigurationBySlug($slug)
            : $this->products->findActiveConfiguration($slug);

        return $product ?? throw new NotFoundHttpException('Product not found.');
    }

    /**
     * Validate selections and return the authoritative price + visibility.
     *
     * @param  array<string, mixed>  $rawSelections
     * @return array{
     *     product: Product,
     *     selections: array<string, array<int, string>>,
     *     visible: array<int, string>,
     *     price: array{base:int,total:int,currency:string,items:array<int,array<string,mixed>>}
     * }
     *
     * @throws ValidationException
     */
    public function quote(string $slug, array $rawSelections, bool $includeDraft = false): array
    {
        $product = $this->configuration($slug, $includeDraft);
        $selections = $this->normalize($rawSelections);

        $visible = $this->resolver->visibleOptionKeys($product, $selections);

        $this->validate($product, $selections, $visible);

        $price = $this->calculator->calculate($product, $selections, $visible);

        return [
            'product' => $product,
            'selections' => $selections,
            'visible' => $visible,
            'price' => $price,
        ];
    }

    /**
     * Normalise raw input to `key => string[]`.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, array<int, string>>
     */
    private function normalize(array $raw): array
    {
        $normalized = [];

        foreach ($raw as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $normalized[$key] = array_values(array_map(
                static fn ($v): string => (string) $v,
                is_array($value) ? $value : [$value],
            ));
        }

        return $normalized;
    }

    /**
     * @param  array<string, array<int, string>>  $selections
     * @param  array<int, string>  $visible
     *
     * @throws ValidationException
     */
    private function validate(Product $product, array $selections, array $visible): void
    {
        $errors = [];

        foreach ($product->options as $option) {
            if (! in_array($option->key, $visible, true)) {
                continue; // hidden options are not validated
            }

            $selected = $selections[$option->key] ?? [];

            if ($option->is_required && $selected === []) {
                $errors[$option->key][] = "The {$option->label} option is required.";

                continue;
            }

            if ($selected === []) {
                continue;
            }

            if ($option->type->hasValues()) {
                $validValues = $option->values->pluck('value')->all();

                if (! $option->type->isMultiple() && count($selected) > 1) {
                    $errors[$option->key][] = "The {$option->label} option accepts a single value.";
                }

                foreach ($selected as $value) {
                    if (! in_array($value, $validValues, true)) {
                        $errors[$option->key][] = "Invalid value [{$value}] for {$option->label}.";
                    }
                }
            } else {
                $max = (int) ($option->config['max_length'] ?? 0);
                if ($max > 0 && mb_strlen($selected[0]) > $max) {
                    $errors[$option->key][] = "The {$option->label} option exceeds {$max} characters.";
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
