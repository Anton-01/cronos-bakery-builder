<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\DTO;

use App\Shared\Application\DTO\DataTransferObject;
use Illuminate\Support\Str;

final class PageData extends DataTransferObject
{
    /**
     * @param  array<int, PageBlockData>|null  $blocks
     */
    public function __construct(
        public readonly ?int $brandId,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $type,
        public readonly ?string $metaTitle,
        public readonly ?string $metaDescription,
        public readonly ?string $content,
        public readonly ?array $settings,
        public readonly string $status,
        public readonly ?array $blocks = null,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new self(
            brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            title: $data['title'],
            slug: $data['slug'] ?? Str::slug($data['title']),
            type: $data['type'],
            metaTitle: $data['meta_title'] ?? null,
            metaDescription: $data['meta_description'] ?? null,
            content: $data['content'] ?? null,
            settings: $data['settings'] ?? null,
            status: $data['status'] ?? 'draft',
            blocks: isset($data['blocks'])
                ? array_map(static fn (array $block): PageBlockData => PageBlockData::fromArray($block), $data['blocks'])
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $attributes = [
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'content' => $this->content,
            'settings' => $this->settings,
            'status' => $this->status,
        ];

        // Absent on updates: the brand of an existing page is immutable.
        if ($this->brandId !== null) {
            $attributes['brand_id'] = $this->brandId;
        }

        return $attributes;
    }
}
