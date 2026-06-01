<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers\Admin;

use App\Modules\CMS\Application\Services\MenuService;
use App\Modules\CMS\Presentation\Http\Requests\StoreMenuItemRequest;
use App\Modules\CMS\Presentation\Http\Resources\MenuItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class MenuItemController extends Controller
{
    public function __construct(private readonly MenuService $menus)
    {
    }

    public function store(StoreMenuItemRequest $request, string $menu): JsonResponse
    {
        $item = $this->menus->addItem($menu, $request->validated());

        return (new MenuItemResource($item))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StoreMenuItemRequest $request, string $menu, string $item): MenuItemResource
    {
        return new MenuItemResource($this->menus->updateItem($menu, $item, $request->validated()));
    }

    public function destroy(string $menu, string $item): JsonResponse
    {
        $this->menus->removeItem($menu, $item);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
