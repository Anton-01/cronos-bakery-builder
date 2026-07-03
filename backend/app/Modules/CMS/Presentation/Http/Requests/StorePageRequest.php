<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Requests;

use App\Modules\CMS\Application\Validation\BlockRules;
use App\Modules\CMS\Domain\Enums\BlockType;
use App\Modules\CMS\Domain\Enums\PageStatus;
use App\Modules\CMS\Domain\Enums\PageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'integer', Rule::exists('brands', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $this->uniqueSlugRule(),
            ],
            'type' => ['required', Rule::enum(PageType::class)],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'status' => ['required', Rule::enum(PageStatus::class)],

            // Optional initial set of builder blocks, validated per type below.
            'blocks' => ['sometimes', 'array'],
            'blocks.*.section_id' => ['nullable', 'integer', 'exists:cms_sections,id', 'required_without:blocks.*.type'],
            'blocks.*.type' => ['nullable', 'required_without:blocks.*.section_id', Rule::enum(BlockType::class)],
            'blocks.*.is_active' => ['sometimes', 'boolean'],
            ...$this->blockPayloadRules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('title')) {
            $this->merge(['slug' => Str::slug((string) $this->input('title'))]);
        }
    }

    /**
     * A slug must be unique within the brand, not globally.
     */
    protected function uniqueSlugRule(): \Illuminate\Validation\Rules\Unique
    {
        $rule = Rule::unique('cms_pages', 'slug')
            ->where('brand_id', (int) $this->input('brand_id'));

        if ($this->route('page') !== null) {
            $rule->ignore($this->route('page'));
        }

        return $rule;
    }

    /**
     * Dynamic, type-specific rules for each inline block payload in the request.
     *
     * @return array<string, mixed>
     */
    protected function blockPayloadRules(): array
    {
        $rules = [];

        foreach ((array) $this->input('blocks', []) as $index => $block) {
            $type = is_array($block) ? BlockType::tryFrom((string) ($block['type'] ?? '')) : null;

            if ($type !== null && empty($block['section_id'])) {
                $rules += BlockRules::forType($type, "blocks.{$index}.data");
            } else {
                // Section-backed blocks may carry partial overrides only.
                $rules["blocks.{$index}.data"] = ['nullable', 'array'];
            }
        }

        return $rules;
    }
}
