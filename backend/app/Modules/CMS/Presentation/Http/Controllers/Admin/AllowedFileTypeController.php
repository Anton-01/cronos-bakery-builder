<?php

declare(strict_types=1);

namespace App\Modules\CMS\Presentation\Http\Controllers\Admin;

use App\Modules\CMS\Domain\Models\AllowedFileType;
use App\Modules\CMS\Presentation\Http\Requests\UpdateAllowedFileTypeRequest;
use App\Modules\CMS\Presentation\Http\Resources\AllowedFileTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * Gestión del catálogo de tipos de archivo de la Media Library. El catálogo
 * lo puebla el Seeder Maestro; el admin habitualmente solo activa/desactiva.
 */
class AllowedFileTypeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $types = AllowedFileType::query()
            ->when($request->boolean('only_active'), fn ($query) => $query->active())
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return AllowedFileTypeResource::collection($types);
    }

    public function update(UpdateAllowedFileTypeRequest $request, AllowedFileType $fileType): AllowedFileTypeResource
    {
        $fileType->update($request->validated());

        return new AllowedFileTypeResource($fileType->refresh());
    }
}
