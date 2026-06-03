<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Requests;

use App\Modules\ProductBuilder\Domain\Enums\PriceModifierType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOptionValueRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:255'],
            'value' => [
                'required', 'string', 'max:255',
                Rule::unique('pb_option_values', 'value')
                    ->where('option_id', $this->route('option'))
                    ->ignore($this->route('value')),
            ],
            'price_modifier_type' => ['required', Rule::enum(PriceModifierType::class)],
            'price_modifier_amount' => ['required', 'integer'],
            'metadata' => ['nullable', 'array'],
            'is_default' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
