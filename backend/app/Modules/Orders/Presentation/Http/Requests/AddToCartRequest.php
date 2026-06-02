<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
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
            'product_slug' => ['required', 'string'],
            'selections' => ['present', 'array'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }
}
