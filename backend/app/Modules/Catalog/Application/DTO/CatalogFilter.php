<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTO;

use App\Shared\Application\DTO\DataTransferObject;

/**
 * Normalised catalog filtering criteria parsed from query parameters.
 */
final class CatalogFilter extends DataTransferObject
{
    /**
     * @param  array<string, array<int, string>>  $attributes  code => values
     */
    public function __construct(
        public readonly ?string $category = null,
        public readonly ?string $collection = null,
        public readonly ?string $tag = null,
        public readonly ?int $priceMin = null,
        public readonly ?int $priceMax = null,
        public readonly array $attributes = [],
        public readonly ?string $search = null,
        public readonly string $sort = 'position',
        public readonly int $perPage = 12,
    ) {
    }

    public static function fromArray(array $data): static
    {
        $attributes = [];
        foreach ((array) ($data['attributes'] ?? []) as $code => $values) {
            $list = is_array($values) ? $values : explode(',', (string) $values);
            $attributes[(string) $code] = array_values(array_filter(array_map('trim', $list)));
        }

        return new self(
            category: $data['category'] ?? null,
            collection: $data['collection'] ?? null,
            tag: $data['tag'] ?? null,
            priceMin: isset($data['price_min']) ? (int) $data['price_min'] : null,
            priceMax: isset($data['price_max']) ? (int) $data['price_max'] : null,
            attributes: $attributes,
            search: $data['search'] ?? null,
            sort: $data['sort'] ?? 'position',
            perPage: min(60, max(1, (int) ($data['per_page'] ?? 12))),
        );
    }
}
