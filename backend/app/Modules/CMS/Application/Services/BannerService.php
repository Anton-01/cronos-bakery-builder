<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\Services;

use App\Modules\CMS\Domain\Enums\BannerPlacement;
use App\Modules\CMS\Domain\Models\Banner;
use Illuminate\Support\Collection;

/**
 * CRUD plus public retrieval for administrable banners.
 */
final class BannerService
{
    public function all(): Collection
    {
        return Banner::query()->orderBy('placement')->orderBy('sort_order')->get();
    }

    /**
     * Live banners for a placement, ordered for display.
     */
    public function liveFor(BannerPlacement $placement): Collection
    {
        return Banner::query()
            ->live()
            ->where('placement', $placement->value)
            ->orderBy('sort_order')
            ->get();
    }

    public function get(int $id): Banner
    {
        return Banner::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Banner
    {
        return Banner::create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(int $id, array $attributes): Banner
    {
        $banner = $this->get($id);
        $banner->update($attributes);

        return $banner->refresh();
    }

    public function delete(int $id): void
    {
        $this->get($id)->delete();
    }
}
