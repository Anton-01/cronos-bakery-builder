<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Actualización PARCIAL de un tema: solo se validan y persisten las claves
 * presentes en el payload (el Theme Builder guarda por pestaña).
 */
class UpdateThemeRequest extends FormRequest
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
        $hex = ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'];

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'favicon' => ['sometimes', 'nullable', 'string', 'max:2048'],

            // Paleta legada (compatibilidad con el storefront actual).
            'colors' => ['sometimes', 'array'],
            'colors.*' => ['string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],

            'fonts' => ['sometimes', 'array'],
            'fonts.heading' => ['sometimes', 'string', 'max:255'],
            'fonts.body' => ['sometimes', 'string', 'max:255'],
            'fonts.stylesheet' => ['nullable', 'string', 'max:2048'],

            // --- Theme Builder PRO (JSONB) ---------------------------------
            'color_palette' => ['sometimes', 'nullable', 'array'],
            'color_palette.primary' => $hex,
            'color_palette.secondary' => $hex,
            'color_palette.accent' => $hex,
            'color_palette.background' => $hex,
            'color_palette.surface' => $hex,
            'color_palette.text' => $hex,

            'typography_settings' => ['sometimes', 'nullable', 'array'],
            'typography_settings.heading_font' => ['nullable', 'string', 'max:255'],
            'typography_settings.body_font' => ['nullable', 'string', 'max:255'],
            'typography_settings.heading_weight' => ['nullable', 'string', 'max:10'],
            'typography_settings.body_weight' => ['nullable', 'string', 'max:10'],
            'typography_settings.base_font_size' => ['nullable', 'integer', 'between:12,24'],

            'layout_config' => ['sometimes', 'nullable', 'array'],
            'layout_config.header_sticky' => ['nullable', 'boolean'],
            'layout_config.footer_expanded' => ['nullable', 'boolean'],
            'layout_config.container_width' => ['nullable', Rule::in(['boxed', 'wide', 'full'])],
            'layout_config.show_breadcrumbs' => ['nullable', 'boolean'],
            'layout_config.product_grid_columns' => ['nullable', 'integer', 'between:2,5'],

            'custom_scripts' => ['sometimes', 'nullable', 'array'],
            'custom_scripts.head' => ['nullable', 'string', 'max:20000'],
            'custom_scripts.body_start' => ['nullable', 'string', 'max:20000'],
            'custom_scripts.body_end' => ['nullable', 'string', 'max:20000'],

            // Configuración de tienda (moneda, impuestos, zona horaria…).
            'settings' => ['sometimes', 'nullable', 'array'],

            'footer' => ['sometimes', 'nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Solo las claves realmente enviadas, mapeando logo/favicon a columnas.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $data = $this->validated();
        $attributes = [];

        foreach (['name', 'colors', 'fonts', 'color_palette', 'typography_settings', 'layout_config', 'custom_scripts', 'settings', 'footer', 'is_active'] as $key) {
            if (array_key_exists($key, $data)) {
                $attributes[$key] = $data[$key];
            }
        }

        if (array_key_exists('logo', $data)) {
            $attributes['logo_path'] = $data['logo'];
        }
        if (array_key_exists('favicon', $data)) {
            $attributes['favicon_path'] = $data['favicon'];
        }

        return $attributes;
    }
}
