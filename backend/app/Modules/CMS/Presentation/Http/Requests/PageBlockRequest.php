<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Requests;

use App\Modules\CMS\Application\Validation\BlockRules;
use App\Modules\CMS\Domain\Enums\BlockType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageBlockRequest extends FormRequest
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
        $rules = [
            // Either reference a reusable section or supply an inline type.
            'section_id' => ['nullable', 'integer', 'exists:cms_sections,id', 'required_without:type'],
            'type' => ['nullable', 'required_without:section_id', Rule::enum(BlockType::class)],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];

        $type = BlockType::tryFrom((string) $this->input('type'));

        if ($type !== null && ! $this->filled('section_id')) {
            // Inline blocks carry a full, type-validated payload.
            $rules += BlockRules::forType($type);
        } else {
            // Section-backed blocks may carry partial overrides only.
            $rules['data'] = ['nullable', 'array'];
        }

        return $rules;
    }
}
