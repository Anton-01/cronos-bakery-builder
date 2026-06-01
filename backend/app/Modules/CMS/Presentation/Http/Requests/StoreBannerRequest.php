<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Requests;

use App\Modules\CMS\Domain\Enums\BannerPlacement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBannerRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'image_path' => ['required', 'string', 'max:2048'],
            'link_url' => ['nullable', 'string', 'max:2048'],
            'placement' => ['required', Rule::enum(BannerPlacement::class)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
