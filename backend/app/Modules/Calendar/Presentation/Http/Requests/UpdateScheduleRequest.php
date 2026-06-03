<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
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
            'days' => ['required', 'array', 'min:1'],
            'days.*.weekday' => ['required', 'integer', 'between:0,6'],
            'days.*.is_open' => ['boolean'],
            'days.*.capacity' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
