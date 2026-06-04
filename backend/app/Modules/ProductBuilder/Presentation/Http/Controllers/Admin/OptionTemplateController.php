<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin;

use App\Modules\ProductBuilder\Domain\Models\OptionTemplate;
use App\Modules\ProductBuilder\Presentation\Http\Resources\OptionTemplateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class OptionTemplateController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $templates = OptionTemplate::with('values')->orderBy('position')->get();

        return OptionTemplateResource::collection($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'unique:pb_option_templates,key'],
            'label' => ['required', 'string'],
            'type' => ['required', 'string'],
            'help_text' => ['nullable', 'string'],
            'is_required' => ['boolean'],
            'position' => ['integer'],
            'config' => ['nullable', 'array'],
        ]);

        $template = OptionTemplate::create($data);
        $template->load('values');

        return (new OptionTemplateResource($template))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(Request $request, OptionTemplate $template): OptionTemplateResource
    {
        $data = $request->validate([
            'key' => ['sometimes', 'string', 'unique:pb_option_templates,key,' . $template->id],
            'label' => ['sometimes', 'string'],
            'type' => ['sometimes', 'string'],
            'help_text' => ['nullable', 'string'],
            'is_required' => ['boolean'],
            'position' => ['integer'],
            'config' => ['nullable', 'array'],
        ]);

        $template->update($data);
        $template->load('values');

        return new OptionTemplateResource($template);
    }

    public function destroy(OptionTemplate $template): JsonResponse
    {
        $template->delete();

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
