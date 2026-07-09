<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin;

use App\Modules\ProductBuilder\Application\Services\ProductAdminService;
use App\Modules\ProductBuilder\Presentation\Http\Requests\StoreOptionRequest;
use App\Modules\ProductBuilder\Presentation\Http\Resources\OptionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class OptionController extends Controller
{
    public function __construct(private readonly ProductAdminService $service)
    {
    }

    public function store(StoreOptionRequest $request, int $product): JsonResponse
    {
        $option = $this->service->addOption($product, $request->validated());

        return (new OptionResource($option))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StoreOptionRequest $request, int $product, int $option): OptionResource
    {
        return new OptionResource($this->service->updateOption($product, $option, $request->validated()));
    }

    public function destroy(int $product, int $option): JsonResponse
    {
        $this->service->deleteOption($product, $option);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
