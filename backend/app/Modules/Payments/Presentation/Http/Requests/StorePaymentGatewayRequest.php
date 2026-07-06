<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Requests;

use App\Modules\Payments\Domain\Enums\GatewayEnvironment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentGatewayRequest extends FormRequest
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
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'driver_name' => [
                'required', 'string',
                // Dynamic: any driver registered in config/payments.php.
                Rule::in(array_keys((array) config('payments.drivers', []))),
                // One live instance per (brand, driver); soft-deleted rows don't block.
                Rule::unique('payment_gateways', 'driver_name')
                    ->where('brand_id', $this->input('brand_id'))
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:120'],
            'environment' => ['required', Rule::enum(GatewayEnvironment::class)],
            'is_active' => ['boolean'],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
