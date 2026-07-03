<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers\Admin;

use App\Modules\CMS\Application\Services\BannerService;
use App\Modules\CMS\Presentation\Http\Requests\StoreBannerRequest;
use App\Modules\CMS\Presentation\Http\Resources\BannerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class BannerController extends Controller
{
    public function __construct(private readonly BannerService $banners)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return BannerResource::collection($this->banners->all());
    }

    public function show(int $banner): BannerResource
    {
        return new BannerResource($this->banners->get($banner));
    }

    public function store(StoreBannerRequest $request): JsonResponse
    {
        $banner = $this->banners->create($request->validated());

        return (new BannerResource($banner))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StoreBannerRequest $request, int $banner): BannerResource
    {
        return new BannerResource($this->banners->update($banner, $request->validated()));
    }

    public function destroy(int $banner): JsonResponse
    {
        $this->banners->delete($banner);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
