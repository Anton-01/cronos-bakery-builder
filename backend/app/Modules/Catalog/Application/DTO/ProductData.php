<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTO;

use App\Shared\Application\DTO\DataTransferObject;
use Illuminate\Support\Str;

/**
 * Immutable carrier for product creation/update input.
 */
final class ProductData extends DataTransferObject
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly int $priceAmount,
        public readonly string $currency,
        public readonly bool $isActive,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'] ?? Str::slug($data['name']),
            description: $data['description'] ?? null,
            priceAmount: (int) $data['price_amount'],
            currency: $data['currency'] ?? 'USD',
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    /**
     * Map to the column names expected by the Product model.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price_amount' => $this->priceAmount,
            'currency' => $this->currency,
            'is_active' => $this->isActive,
        ];
    }
}
