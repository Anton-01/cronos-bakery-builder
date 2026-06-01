<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\DTO;

use App\Shared\Application\DTO\DataTransferObject;
use Illuminate\Support\Str;

final class PageData extends DataTransferObject
{
    public function __construct(
        public readonly string $title,
        public readonly string $slug,
        public readonly string $type,
        public readonly ?string $metaTitle,
        public readonly ?string $metaDescription,
        public readonly ?string $content,
        public readonly string $status,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new self(
            title: $data['title'],
            slug: $data['slug'] ?? Str::slug($data['title']),
            type: $data['type'],
            metaTitle: $data['meta_title'] ?? null,
            metaDescription: $data['meta_description'] ?? null,
            content: $data['content'] ?? null,
            status: $data['status'] ?? 'draft',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'content' => $this->content,
            'status' => $this->status,
        ];
    }
}
