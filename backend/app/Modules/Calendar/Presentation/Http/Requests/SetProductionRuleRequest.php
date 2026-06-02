<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetProductionRuleRequest extends FormRequest
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
            'product_id' => ['nullable', 'uuid'],
            'lead_time_hours' => ['required', 'integer', 'min:0', 'max:8760'],
        ];
    }
}
