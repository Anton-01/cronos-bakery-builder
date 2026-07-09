<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Requests;

use App\Modules\ProductBuilder\Domain\Models\ProductOptionLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductOptionLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize incoming IDs to integers before validation so the exists
     * check compares against the identity PKs consistently.
     */
    protected function prepareForValidation(): void
    {
        $excluded = $this->input('excluded_value_ids');

        if (is_array($excluded)) {
            $this->merge([
                'excluded_value_ids' => array_values(array_map(
                    static fn ($id) => is_numeric($id) ? (int) $id : $id,
                    $excluded,
                )),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'legend' => ['nullable', 'string'],
            'excluded_value_ids' => ['nullable', 'array'],
            'excluded_value_ids.*' => [
                'integer',
                // Each excluded value must belong to the already-linked template.
                Rule::exists('pb_option_template_values', 'id')
                    ->where('template_id', $this->linkedTemplateId()),
            ],
            'position' => ['integer'],
        ];
    }

    /**
     * Template ID of the link being updated. The route param may arrive as the
     * bound model or as its raw ID depending on middleware order, so both are
     * handled defensively.
     */
    private function linkedTemplateId(): ?int
    {
        $link = $this->route('link');

        if ($link instanceof ProductOptionLink) {
            return (int) $link->template_id;
        }

        if (is_string($link) || is_int($link)) {
            $templateId = ProductOptionLink::query()->whereKey($link)->value('template_id');

            return $templateId === null ? null : (int) $templateId;
        }

        return null;
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
