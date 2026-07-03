<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Requests;

/**
 * Same contract as {@see StorePageRequest} — the route-bound page id is
 * ignored by the per-brand unique-slug rule — except that the brand of an
 * existing page is immutable: content never moves between tenants.
 */
class UpdatePageRequest extends StorePageRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // The brand is fixed at creation time; reuse the current one for the
        // slug-uniqueness scope and reject attempts to change it.
        $rules['brand_id'] = ['prohibited'];

        return $rules;
    }

    protected function uniqueSlugRule(): \Illuminate\Validation\Rules\Unique
    {
        $page = \App\Modules\CMS\Domain\Models\Page::query()->findOrFail((int) $this->route('page'));

        return \Illuminate\Validation\Rule::unique('cms_pages', 'slug')
            ->where('brand_id', $page->brand_id)
            ->ignore($page->id);
    }
}
