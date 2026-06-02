<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReminderRuleRequest extends FormRequest
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
            'offset_hours' => [
                'required', 'integer', 'min:1', 'max:720',
                Rule::unique('reminder_rules', 'offset_hours')->ignore($this->route('rule')),
            ],
            'is_active' => ['boolean'],
        ];
    }
}
