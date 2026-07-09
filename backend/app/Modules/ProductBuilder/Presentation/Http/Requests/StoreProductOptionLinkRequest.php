<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductOptionLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize incoming IDs to integers before validation so the exists
     * checks compare against the identity PKs consistently.
     */
    protected function prepareForValidation(): void
    {
        $excluded = $this->input('excluded_value_ids');

        $this->merge([
            'template_id' => is_numeric($this->input('template_id'))
                ? (int) $this->input('template_id')
                : $this->input('template_id'),
            'excluded_value_ids' => is_array($excluded)
                ? array_values(array_map(
                    static fn ($id) => is_numeric($id) ? (int) $id : $id,
                    $excluded,
                ))
                : $excluded,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', 'exists:pb_option_templates,id'],
            'legend' => ['nullable', 'string'],
            'excluded_value_ids' => ['nullable', 'array'],
            'excluded_value_ids.*' => [
                'integer',
                // Each excluded value must belong to the template being linked.
                Rule::exists('pb_option_template_values', 'id')
                    ->where('template_id', (int) $this->input('template_id')),
            ],
            'position' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'excluded_value_ids.*.exists' => 'Cada valor excluido debe pertenecer a la opción vinculada.',
        ];
    }
}
