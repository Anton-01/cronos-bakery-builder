<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Requests;

use App\Modules\ProductBuilder\Domain\Enums\OptionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOptionRequest extends FormRequest
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
            'key' => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('pb_options', 'key')
                    ->where('product_id', $this->route('product'))
                    ->ignore($this->route('option')),
            ],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(OptionType::class)],
            'help_text' => ['nullable', 'string', 'max:500'],
            'is_required' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            'config' => ['nullable', 'array'],
        ];
    }
}
