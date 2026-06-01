<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreThemeRequest extends FormRequest
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
        $hex = ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:2048'],
            'favicon' => ['nullable', 'string', 'max:2048'],

            'colors' => ['required', 'array'],
            'colors.primary' => $hex,
            'colors.secondary' => $hex,
            'colors.accent' => $hex,
            'colors.success' => $hex,
            'colors.warning' => $hex,
            'colors.danger' => $hex,

            'fonts' => ['required', 'array'],
            'fonts.heading' => ['required', 'string', 'max:255'],
            'fonts.body' => ['required', 'string', 'max:255'],
            'fonts.stylesheet' => ['nullable', 'string', 'max:2048'],

            'footer' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Map API field names (logo/favicon) to the model columns.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'logo_path' => $data['logo'] ?? null,
            'favicon_path' => $data['favicon'] ?? null,
            'colors' => $data['colors'],
            'fonts' => $data['fonts'],
            'footer' => $data['footer'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
