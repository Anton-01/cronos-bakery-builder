<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Requests;

use App\Modules\Orders\Domain\Enums\AddressLabel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAddressRequest extends FormRequest
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
            'label' => ['required', Rule::enum(AddressLabel::class)],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:2'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
        ];
    }
}
