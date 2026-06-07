<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'slug' => [
                'nullable', 'string', 'max:255',
                Rule::unique('pb_products', 'slug')->ignore($this->route('product')),
            ],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:2048'],
            'base_price_amount' => ['required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'discount_type' => ['nullable', 'string', 'in:none,percentage,fixed'],
            'discount_value' => ['nullable', 'integer', 'min:0'],
            'tax_class' => ['nullable', 'string', 'in:standard,reduced,zero'],
            'vat' => ['nullable', 'integer', 'min:0', 'max:100'],
            'tags' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $data = $this->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['currency'] = $data['currency'] ?? 'USD';
        $data['discount_type'] = $data['discount_type'] ?? 'none';
        $data['tax_class'] = $data['tax_class'] ?? 'standard';

        return $data;
    }
}
