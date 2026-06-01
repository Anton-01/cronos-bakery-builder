<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Requests;

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
        return [
            // Either reference a reusable section or supply an inline type.
            'section_id' => ['nullable', 'uuid', 'exists:cms_sections,id', 'required_without:type'],
            'type' => ['nullable', 'required_without:section_id', Rule::enum(BlockType::class)],
            'data' => ['nullable', 'array'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
