<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers;

use App\Modules\Catalog\Application\DTO\CatalogFilter;
use App\Modules\Catalog\Application\Services\ProductBrowseService;
use App\Modules\Catalog\Presentation\Http\Resources\AttributeResource;
use App\Modules\Catalog\Presentation\Http\Resources\CatalogProductResource;
use App\Modules\Catalog\Presentation\Http\Resources\CategoryResource;
use App\Modules\Catalog\Presentation\Http\Resources\CollectionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public storefront catalog: dynamic filtering, facets, SEO category landing
 * pages and product detail — all consumed by the Vue frontend.
 */
class CatalogController extends Controller
{
    public function __construct(private readonly ProductBrowseService $browse)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filter = CatalogFilter::fromArray($request->all());

        return CatalogProductResource::collection($this->browse->filter($filter));
    }

    /**
     * Configurable filter facets for the storefront UI.
     */
    public function facets(): JsonResponse
    {
        $facets = $this->browse->facets();

        return response()->json([
            'data' => [
                'categories' => CategoryResource::collection($facets['categories']),
                'collections' => CollectionResource::collection($facets['collections']),
                'attributes' => AttributeResource::collection($facets['attributes']),
                'price' => $facets['price'],
            ],
        ]);
    }

    /**
     * SEO category landing page (/categoria/{slug}): category, breadcrumbs and
     * its filtered products.
     */
    public function category(Request $request, string $slug): JsonResponse
    {
        $category = $this->browse->categoryBySlug($slug)
            ?? throw new NotFoundHttpException('Category not found.');

        $filter = CatalogFilter::fromArray(array_merge($request->all(), ['category' => $slug]));
        $products = $this->browse->filter($filter);

        return response()->json([
            'data' => [
                'category' => new CategoryResource($category),
                'breadcrumbs' => $this->browse->categoryBreadcrumbs($category),
                'products' => CatalogProductResource::collection($products)->response()->getData(true),
            ],
        ]);
    }

    /**
     * Product detail by slug (/pastel/{slug}) with breadcrumbs.
     */
    public function show(string $slug): JsonResponse
    {
        $product = $this->browse->productBySlug($slug)
            ?? throw new NotFoundHttpException('Product not found.');

        return response()->json([
            'data' => [
                'product' => new CatalogProductResource($product),
                'breadcrumbs' => $this->browse->productBreadcrumbs($product),
            ],
        ]);
    }
}
