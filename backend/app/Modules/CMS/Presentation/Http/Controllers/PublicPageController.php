<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers;

use App\Modules\CMS\Application\Services\PageService;
use App\Modules\CMS\Domain\Models\Brand;
use App\Modules\CMS\Presentation\Http\Resources\PageResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public, read-only access to published CMS pages — consumed by the Vue
 * frontend to render dynamic pages from stored configuration. The tenant is
 * resolved from the optional `brand` query parameter (brand slug); when
 * omitted, the platform's default (first active) brand is used.
 */
class PublicPageController extends Controller
{
    public function __construct(private readonly PageService $pages)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return PageResource::collection(
            $this->pages->publishedPages($this->resolveBrandId($request)),
        );
    }

    public function show(Request $request, string $slug): PageResource
    {
        $page = $this->pages->publicBySlug($slug, $this->resolveBrandId($request));

        if ($page === null) {
            throw new NotFoundHttpException('Page not found.');
        }

        return new PageResource($page);
    }

    private function resolveBrandId(Request $request): ?int
    {
        $brandSlug = $request->query('brand');

        if (is_string($brandSlug) && $brandSlug !== '') {
            $brandId = Brand::query()->active()->where('slug', $brandSlug)->value('id');

            if ($brandId === null) {
                throw new NotFoundHttpException('Brand not found.');
            }

            return (int) $brandId;
        }

        return Brand::query()->active()->orderBy('id')->value('id');
    }
}
