<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin;

use App\Modules\ProductBuilder\Application\Services\ProductAdminService;
use App\Modules\ProductBuilder\Presentation\Http\Requests\StoreOptionRuleRequest;
use App\Modules\ProductBuilder\Presentation\Http\Resources\OptionRuleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class OptionRuleController extends Controller
{
    public function __construct(private readonly ProductAdminService $service)
    {
    }

    public function store(StoreOptionRuleRequest $request, int $product): JsonResponse
    {
        $rule = $this->service->addRule($product, $request->validated());

        return (new OptionRuleResource($rule))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function destroy(int $product, int $rule): JsonResponse
    {
        $this->service->deleteRule($product, $rule);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
