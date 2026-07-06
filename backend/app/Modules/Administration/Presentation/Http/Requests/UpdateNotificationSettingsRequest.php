<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public const CHANNELS = ['order_updates', 'production_alerts', 'security_alerts', 'weekly_summary', 'marketing'];

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
            'settings' => ['required', 'array'],
            'settings.*' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Only known channels are persisted — unknown keys are dropped.
        $settings = collect((array) $this->input('settings', []))
            ->only(self::CHANNELS)
            ->all();

        $this->merge(['settings' => $settings]);
    }
}
