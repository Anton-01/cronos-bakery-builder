<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAllowedFileTypeRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:100'],
            'category' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'mime_types' => ['sometimes', 'array', 'min:1'],
            'mime_types.*' => ['string', 'max:255', 'regex:#^[\w.+-]+/[\w.+-]+$#'],
            'extensions' => ['sometimes', 'array', 'min:1'],
            'extensions.*' => ['string', 'max:20', 'regex:/^[a-z0-9]+$/i'],
            'icon_reference' => ['sometimes', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
