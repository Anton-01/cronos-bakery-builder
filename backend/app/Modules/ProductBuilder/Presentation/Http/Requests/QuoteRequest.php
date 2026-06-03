<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteRequest extends FormRequest
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
            // Map of option key => value (string) or values (array) for checkboxes.
            'selections' => ['present', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function selections(): array
    {
        return $this->validated('selections') ?? [];
    }
}
