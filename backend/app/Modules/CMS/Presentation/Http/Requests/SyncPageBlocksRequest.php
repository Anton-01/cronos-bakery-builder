<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Requests;

use App\Modules\CMS\Application\Validation\BlockRules;
use App\Modules\CMS\Domain\Enums\BlockType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Bulk save of a page's builder state: the request carries the full ordered
 * list of blocks. Existing blocks keep their `id`, new ones come without it,
 * and any block missing from the list is deleted. Positions follow array order.
 */
class SyncPageBlocksRequest extends FormRequest
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
        $rules = [
            'blocks' => ['present', 'array'],
            'blocks.*.id' => [
                'nullable', 'integer',
                Rule::exists('cms_page_blocks', 'id')->where('page_id', (int) $this->route('page')),
            ],
            'blocks.*.section_id' => ['nullable', 'integer', 'exists:cms_sections,id', 'required_without:blocks.*.type'],
            'blocks.*.type' => ['nullable', 'required_without:blocks.*.section_id', Rule::enum(BlockType::class)],
            'blocks.*.is_active' => ['sometimes', 'boolean'],
        ];

        foreach ((array) $this->input('blocks', []) as $index => $block) {
            $type = is_array($block) ? BlockType::tryFrom((string) ($block['type'] ?? '')) : null;

            if ($type !== null && empty($block['section_id'])) {
                $rules += BlockRules::forType($type, "blocks.{$index}.data");
            } else {
                $rules["blocks.{$index}.data"] = ['nullable', 'array'];
            }
        }

        return $rules;
    }
}
