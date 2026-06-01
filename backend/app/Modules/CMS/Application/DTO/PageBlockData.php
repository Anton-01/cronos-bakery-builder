<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\DTO;

use App\Shared\Application\DTO\DataTransferObject;

/**
 * Input for a single builder block on a page. Either inline (`type` + `data`)
 * or a reference to a reusable section (`section_id`).
 */
final class PageBlockData extends DataTransferObject
{
    public function __construct(
        public readonly ?string $sectionId,
        public readonly ?string $type,
        public readonly ?array $data,
        public readonly int $position,
        public readonly bool $isActive,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new self(
            sectionId: $data['section_id'] ?? null,
            type: $data['type'] ?? null,
            data: $data['data'] ?? null,
            position: (int) ($data['position'] ?? 0),
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'section_id' => $this->sectionId,
            'type' => $this->type,
            'data' => $this->data,
            'position' => $this->position,
            'is_active' => $this->isActive,
        ];
    }
}
