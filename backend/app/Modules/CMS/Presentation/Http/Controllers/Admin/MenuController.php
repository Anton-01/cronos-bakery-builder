<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers\Admin;

use App\Modules\CMS\Application\Services\MenuService;
use App\Modules\CMS\Presentation\Http\Requests\StoreMenuRequest;
use App\Modules\CMS\Presentation\Http\Resources\MenuResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class MenuController extends Controller
{
    public function __construct(private readonly MenuService $menus)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return MenuResource::collection($this->menus->all());
    }

    public function show(int $menu): MenuResource
    {
        return new MenuResource($this->menus->get($menu));
    }

    public function store(StoreMenuRequest $request): JsonResponse
    {
        $menu = $this->menus->create($request->validated());

        return (new MenuResource($menu))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StoreMenuRequest $request, int $menu): MenuResource
    {
        return new MenuResource($this->menus->update($menu, $request->validated()));
    }

    public function destroy(int $menu): JsonResponse
    {
        $this->menus->delete($menu);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
