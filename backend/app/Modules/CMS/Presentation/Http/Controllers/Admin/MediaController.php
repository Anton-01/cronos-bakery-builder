<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers\Admin;

use App\Modules\CMS\Application\Services\MediaLibraryService;
use App\Modules\CMS\Domain\Models\MediaAsset;
use App\Modules\CMS\Presentation\Http\Resources\MediaAssetResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class MediaController extends Controller
{
    public function __construct(private readonly MediaLibraryService $media)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $assets = $this->media->paginate([
            'search' => $request->query('search'),
            'mime' => $request->query('mime'),
            'file_type_id' => $request->query('file_type_id'),
            'per_page' => $request->query('per_page'),
        ]);

        return MediaAssetResource::collection($assets);
    }

    public function store(Request $request): JsonResponse
    {
        // Solo se exige que venga UN archivo; el tipo/tamaño se validan
        // dinámicamente contra `allowed_file_types` en el service.
        $request->validate(['file' => ['required', 'file']]);

        $asset = $this->media->upload($request->file('file'), $request->user()?->id);

        return (new MediaAssetResource($asset))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function destroy(MediaAsset $media): JsonResponse
    {
        $this->media->delete($media);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
