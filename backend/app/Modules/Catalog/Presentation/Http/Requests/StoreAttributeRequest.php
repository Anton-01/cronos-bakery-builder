<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreAttributeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable', 'string', 'max:100', 'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('catalog_attributes', 'code')->ignore($this->route('attribute')),
            ],
            'type' => ['required', Rule::in(['select', 'color'])],
            'is_filterable' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $data = $this->validated();
        $data['code'] = $data['code'] ?? Str::slug($data['name']);

        return $data;
    }
}
