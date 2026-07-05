<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Requests;

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
            // Dynamic: any driver registered in config/payments.php.
            'gateway' => ['required', 'string', Rule::in(array_keys((array) config('payments.drivers', [])))],
        ];
    }
}
