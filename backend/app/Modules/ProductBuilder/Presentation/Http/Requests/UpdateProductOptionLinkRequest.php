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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ProductOptionLink|null $link */
        $link = $this->route('link');

        return [
            'legend' => ['nullable', 'string'],
            'excluded_value_ids' => ['nullable', 'array'],
            'excluded_value_ids.*' => [
                'uuid',
                // Each excluded value must belong to the already-linked template.
                Rule::exists('pb_option_template_values', 'id')
                    ->where('template_id', $link?->template_id),
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
