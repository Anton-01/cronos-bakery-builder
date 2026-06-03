<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin;

use App\Modules\ProductBuilder\Application\Services\ProductAdminService;
use App\Modules\ProductBuilder\Presentation\Http\Requests\StoreOptionValueRequest;
use App\Modules\ProductBuilder\Presentation\Http\Resources\OptionValueResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class OptionValueController extends Controller
{
    public function __construct(private readonly ProductAdminService $service)
    {
    }

    public function store(StoreOptionValueRequest $request, string $product, string $option): JsonResponse
    {
        $value = $this->service->addValue($product, $option, $request->validated());

        return (new OptionValueResource($value))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(
        StoreOptionValueRequest $request,
        string $product,
        string $option,
        string $value,
    ): OptionValueResource {
        return new OptionValueResource($this->service->updateValue($product, $option, $value, $request->validated()));
    }

    public function destroy(string $product, string $option, string $value): JsonResponse
    {
        $this->service->deleteValue($product, $option, $value);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
