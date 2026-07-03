<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers\Admin;

use App\Modules\CMS\Application\Services\ThemeService;
use App\Modules\CMS\Presentation\Http\Requests\StoreThemeRequest;
use App\Modules\CMS\Presentation\Http\Resources\ThemeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class ThemeController extends Controller
{
    public function __construct(private readonly ThemeService $themes)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return ThemeResource::collection($this->themes->all());
    }

    public function store(StoreThemeRequest $request): JsonResponse
    {
        $theme = $this->themes->create($request->toAttributes());

        return (new ThemeResource($theme))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StoreThemeRequest $request, int $theme): ThemeResource
    {
        return new ThemeResource($this->themes->update($theme, $request->toAttributes()));
    }

    public function activate(int $theme): ThemeResource
    {
        return new ThemeResource($this->themes->activate($theme));
    }
}
