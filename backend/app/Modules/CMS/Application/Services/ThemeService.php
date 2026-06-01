<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\Services;

use App\Modules\CMS\Domain\Models\Theme;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Manages storefront branding themes. Exactly one theme is active at a time and
 * is what the public frontend renders.
 */
final class ThemeService
{
    public function all(): Collection
    {
        return Theme::query()->orderBy('name')->get();
    }

    /**
     * The active theme (falling back to the first available).
     */
    public function active(): ?Theme
    {
        return Theme::query()->where('is_active', true)->first()
            ?? Theme::query()->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Theme
    {
        $theme = Theme::create($attributes);

        if ($theme->is_active) {
            $this->activate($theme->id);
        }

        return $theme;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $id, array $attributes): Theme
    {
        $theme = Theme::query()->findOrFail($id);
        $theme->update($attributes);

        if ($theme->is_active) {
            $this->activate($theme->id);
        }

        return $theme->refresh();
    }

    /**
     * Make a single theme active, deactivating the rest.
     */
    public function activate(string $id): Theme
    {
        return DB::transaction(function () use ($id): Theme {
            $theme = Theme::query()->findOrFail($id);

            Theme::query()->where('id', '!=', $id)->update(['is_active' => false]);
            $theme->update(['is_active' => true]);

            return $theme->refresh();
        });
    }
}
