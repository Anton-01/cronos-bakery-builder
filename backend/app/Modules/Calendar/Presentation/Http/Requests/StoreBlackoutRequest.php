<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlackoutRequest extends FormRequest
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
            'date' => ['required', 'date'],
            'time_slot_id' => ['nullable', 'uuid', 'exists:calendar_time_slots,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
