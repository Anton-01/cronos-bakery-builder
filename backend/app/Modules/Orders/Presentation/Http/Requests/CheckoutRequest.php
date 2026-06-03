<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Requests;

use App\Modules\Orders\Domain\Enums\FulfillmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
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
            'fulfillment_type' => ['required', Rule::enum(FulfillmentType::class)],

            // Delivery: an address owned by the customer.
            'address_id' => ['required_if:fulfillment_type,delivery', 'uuid', 'exists:addresses,id'],

            // Pickup: branch + scheduled slot (date must not be in the past).
            'branch_id' => ['required_if:fulfillment_type,pickup', 'uuid', 'exists:branches,id'],
            'pickup_date' => ['required_if:fulfillment_type,pickup', 'date', 'after_or_equal:today'],
            'pickup_time' => ['required_if:fulfillment_type,pickup', 'string', 'max:10'],

            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
