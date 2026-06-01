<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\DTO;

use App\Shared\Application\DTO\DataTransferObject;

/**
 * Input for a reusable section in the library.
 */
final class SectionData extends DataTransferObject
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly array $data,
        public readonly bool $isActive,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            data: $data['data'] ?? [],
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'data' => $this->data,
            'is_active' => $this->isActive,
        ];
    }
}
