<?php

declare(strict_types=1);

namespace App\Modules\CMS\Application\Services;

use App\Modules\CMS\Domain\Enums\MenuLocation;
use App\Modules\CMS\Domain\Models\Menu;
use App\Modules\CMS\Domain\Models\MenuItem;
use Illuminate\Support\Collection;

/**
 * CRUD for dynamic menus and their nested items.
 */
final class MenuService
{
    public function all(): Collection
    {
        return Menu::query()->with('rootItems')->orderBy('name')->get();
    }

    public function get(int $id): Menu
    {
        return Menu::query()->with('rootItems')->findOrFail($id);
    }

    /**
     * The active menu for a location, with its item tree, for public rendering.
     */
    public function activeForLocation(MenuLocation $location): ?Menu
    {
        return Menu::query()
            ->where('location', $location->value)
            ->where('is_active', true)
            ->with('rootItems')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Menu
    {
        return Menu::create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(int $id, array $attributes): Menu
    {
        $menu = Menu::query()->findOrFail($id);
        $menu->update($attributes);

        return $menu->refresh()->load('rootItems');
    }

    public function delete(int $id): void
    {
        Menu::query()->findOrFail($id)->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addItem(int $menuId, array $attributes): MenuItem
    {
        $menu = Menu::query()->findOrFail($menuId);

        $attributes['position'] = $attributes['position']
            ?? (int) $menu->items()->where('parent_id', $attributes['parent_id'] ?? null)->max('position') + 1;

        return $menu->items()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateItem(int $menuId, int $itemId, array $attributes): MenuItem
    {
        $item = MenuItem::query()->where('menu_id', $menuId)->where('id', $itemId)->firstOrFail();
        $item->update($attributes);

        return $item->refresh();
    }

    public function removeItem(int $menuId, int $itemId): void
    {
        MenuItem::query()->where('menu_id', $menuId)->where('id', $itemId)->firstOrFail()->delete();
    }
}
