<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Requests;

use App\Modules\Payments\Domain\Enums\PaymentMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGatewayConfigRequest extends FormRequest
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
            'mode' => ['required', Rule::enum(PaymentMode::class)],
            'credentials' => ['required', 'array'],
            'credentials.webhook_secret' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
