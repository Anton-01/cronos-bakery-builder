<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers;

use App\Modules\CMS\Application\Services\BannerService;
use App\Modules\CMS\Domain\Enums\BannerPlacement;
use App\Modules\CMS\Presentation\Http\Resources\BannerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public read access to live banners for a placement.
 */
class BannerController extends Controller
{
    public function __construct(private readonly BannerService $banners)
    {
    }

    public function index(Request $request, string $placement): AnonymousResourceCollection
    {
        $enum = BannerPlacement::tryFrom($placement);

        if ($enum === null) {
            throw new NotFoundHttpException('Unknown banner placement.');
        }

        return BannerResource::collection($this->banners->liveFor($enum));
    }
}
