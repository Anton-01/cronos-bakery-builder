<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers;

use App\Modules\CMS\Application\Services\PageService;
use App\Modules\CMS\Presentation\Http\Resources\PageResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public, read-only access to published CMS pages — consumed by the Vue
 * frontend to render dynamic pages from stored configuration.
 */
class PublicPageController extends Controller
{
    public function __construct(private readonly PageService $pages)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return PageResource::collection($this->pages->publishedPages());
    }

    public function show(string $slug): PageResource
    {
        $page = $this->pages->publicBySlug($slug);

        if ($page === null) {
            throw new NotFoundHttpException('Page not found.');
        }

        return new PageResource($page);
    }
}
