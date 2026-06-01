<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Requests;

use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:catalog_products,slug'],
            'description' => ['nullable', 'string'],
            'price_amount' => ['required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_active' => ['boolean'],
        ];
    }
}
