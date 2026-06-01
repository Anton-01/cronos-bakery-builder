<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Requests;

use App\Modules\CMS\Domain\Enums\PageStatus;
use App\Modules\CMS\Domain\Enums\PageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePageRequest extends FormRequest
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
        $pageId = $this->route('page');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255',
                Rule::unique('cms_pages', 'slug')->ignore($pageId),
            ],
            'type' => ['required', Rule::enum(PageType::class)],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(PageStatus::class)],
        ];
    }
}
