<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Presentation\Http\Requests;

use App\Modules\Notifications\Domain\Enums\NotificationEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends FormRequest
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
            'event' => [
                'required', Rule::enum(NotificationEvent::class),
                Rule::unique('notification_templates', 'event')->ignore($this->route('template')),
            ],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
