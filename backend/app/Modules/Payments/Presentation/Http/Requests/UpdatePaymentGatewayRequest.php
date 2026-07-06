<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Requests;

use App\Modules\Payments\Domain\Enums\GatewayEnvironment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentGatewayRequest extends FormRequest
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
            // brand_id and driver_name are immutable after creation.
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'environment' => ['sometimes', Rule::enum(GatewayEnvironment::class)],
            'is_active' => ['boolean'],
            // Partial update semantics: only provided keys are touched; a
            // non-empty value overwrites, an explicit null removes the key.
            'credentials' => ['sometimes', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
