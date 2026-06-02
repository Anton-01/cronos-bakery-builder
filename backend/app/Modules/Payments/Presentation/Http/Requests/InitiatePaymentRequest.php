<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Requests;

use App\Modules\Payments\Domain\Enums\GatewayType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
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
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
            'gateway' => ['required', Rule::enum(GatewayType::class)],
        ];
    }
}
