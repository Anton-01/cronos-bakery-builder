<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Admin;

use App\Modules\Catalog\Application\Services\TaxonomyService;
use App\Modules\Catalog\Presentation\Http\Requests\StoreCategoryRequest;
use App\Modules\Catalog\Presentation\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class CategoryController extends Controller
{
    public function __construct(private readonly TaxonomyService $taxonomy)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->taxonomy->categories());
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->taxonomy->createCategory($request->toAttributes());

        return (new CategoryResource($category))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StoreCategoryRequest $request, string $category): CategoryResource
    {
        return new CategoryResource($this->taxonomy->updateCategory($category, $request->toAttributes()));
    }

    public function destroy(string $category): JsonResponse
    {
        $this->taxonomy->deleteCategory($category);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
