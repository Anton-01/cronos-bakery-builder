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
            // Optional admin preview token: allows quoting a draft product.
            'preview_token' => ['nullable', 'string', 'max:128'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function selections(): array
    {
        return $this->validated('selections') ?? [];
    }

    public function previewToken(): ?string
    {
        return $this->validated('preview_token');
    }
}
