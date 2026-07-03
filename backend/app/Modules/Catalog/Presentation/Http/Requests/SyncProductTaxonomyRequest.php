<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncProductTaxonomyRequest extends FormRequest
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
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:catalog_categories,id'],
            'primary_category' => ['nullable', 'integer', 'in_array:categories.*'],
            'collections' => ['array'],
            'collections.*' => ['integer', 'exists:catalog_collections,id'],
            'attribute_values' => ['array'],
            'attribute_values.*' => ['integer', 'exists:catalog_attribute_values,id'],
            'tags' => ['array'],
            'tags.*' => ['integer', 'exists:catalog_tags,id'],
        ];
    }
}
