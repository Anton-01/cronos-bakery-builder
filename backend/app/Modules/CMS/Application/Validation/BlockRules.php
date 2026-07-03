<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\Validation;

use App\Modules\CMS\Domain\Enums\BlockType;

/**
 * Single source of truth for the dynamic, type-specific validation of block
 * payloads. Every entry point that accepts a block payload (single block
 * endpoints and the bulk page/blocks sync) builds its rules from here, so the
 * JSONB stored in cms_page_blocks.data always has a known shape per type.
 */
final class BlockRules
{
    /**
     * Validation rules for one block payload, keyed relative to `$prefix`
     * (e.g. "data" or "blocks.3.data").
     *
     * @return array<string, array<int, mixed>>
     */
    public static function forType(BlockType $type, string $prefix = 'data'): array
    {
        $rules = match ($type) {
            BlockType::Hero => [
                'heading' => ['required', 'string', 'max:150'],
                'subheading' => ['nullable', 'string', 'max:300'],
                'image' => ['nullable', 'string', 'max:2048'],
                'cta_label' => ['nullable', 'string', 'max:80'],
                'cta_url' => ['nullable', 'string', 'max:2048'],
            ],
            BlockType::Banner => [
                'image' => ['required', 'string', 'max:2048'],
                'link' => ['nullable', 'string', 'max:2048'],
                'alt' => ['nullable', 'string', 'max:150'],
            ],
            BlockType::Gallery => [
                'title' => ['nullable', 'string', 'max:150'],
                'images' => ['required', 'array', 'min:1', 'max:24'],
                'images.*.url' => ['required', 'string', 'max:2048'],
                'images.*.caption' => ['nullable', 'string', 'max:200'],
            ],
            BlockType::Cards => [
                'title' => ['nullable', 'string', 'max:150'],
                'items' => ['required', 'array', 'min:1', 'max:12'],
                'items.*.title' => ['required', 'string', 'max:120'],
                'items.*.text' => ['nullable', 'string', 'max:500'],
                'items.*.image' => ['nullable', 'string', 'max:2048'],
            ],
            BlockType::Text => [
                'body' => ['required', 'string', 'max:20000'],
            ],
            BlockType::Video => [
                'url' => ['required', 'string', 'max:2048'],
                'title' => ['nullable', 'string', 'max:150'],
                'autoplay' => ['sometimes', 'boolean'],
            ],
            BlockType::Cta => [
                'heading' => ['required', 'string', 'max:150'],
                'text' => ['nullable', 'string', 'max:500'],
                'cta_label' => ['required', 'string', 'max:80'],
                'cta_url' => ['required', 'string', 'max:2048'],
            ],
            BlockType::Faq => [
                'title' => ['nullable', 'string', 'max:150'],
                'items' => ['required', 'array', 'min:1', 'max:30'],
                'items.*.question' => ['required', 'string', 'max:300'],
                'items.*.answer' => ['required', 'string', 'max:2000'],
            ],
            BlockType::Testimonials => [
                'title' => ['nullable', 'string', 'max:150'],
                'items' => ['required', 'array', 'min:1', 'max:12'],
                'items.*.author' => ['required', 'string', 'max:120'],
                'items.*.quote' => ['required', 'string', 'max:600'],
            ],
            BlockType::Products => [
                'title' => ['nullable', 'string', 'max:150'],
                'source' => ['required', 'string', 'in:latest,featured,category,manual'],
                'category_slug' => ['nullable', 'string', 'max:255', 'required_if:' . $prefix . '.source,category'],
                'product_ids' => ['nullable', 'array', 'max:24', 'required_if:' . $prefix . '.source,manual'],
                'product_ids.*' => ['string', 'max:64'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:24'],
                'show_price' => ['sometimes', 'boolean'],
            ],
        };

        $prefixed = [$prefix => ['required', 'array']];

        foreach ($rules as $key => $constraints) {
            $prefixed[$prefix . '.' . $key] = $constraints;
        }

        return $prefixed;
    }
}
