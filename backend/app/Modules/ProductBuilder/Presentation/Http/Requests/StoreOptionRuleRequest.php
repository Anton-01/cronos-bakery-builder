<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Requests;

use App\Modules\ProductBuilder\Domain\Enums\RuleAction;
use App\Modules\ProductBuilder\Domain\Enums\RuleOperator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOptionRuleRequest extends FormRequest
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
        $product = $this->route('product');

        $belongsToProduct = Rule::exists('pb_options', 'id')->where('product_id', $product);

        return [
            'option_id' => ['required', 'uuid', $belongsToProduct],
            'depends_on_option_id' => ['required', 'uuid', 'different:option_id', $belongsToProduct],
            'operator' => ['required', Rule::enum(RuleOperator::class)],
            'value' => ['required', 'string', 'max:255'],
            'action' => ['required', Rule::enum(RuleAction::class)],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
