<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Presentation\Http\Controllers\Admin;

use App\Modules\ProductBuilder\Domain\Models\OptionTemplate;
use App\Modules\ProductBuilder\Domain\Models\OptionTemplateValue;
use App\Modules\ProductBuilder\Presentation\Http\Resources\OptionTemplateValueResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OptionTemplateValueController extends Controller
{
    public function store(Request $request, OptionTemplate $template): JsonResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string'],
            'value' => ['required', 'string'],
            'price_modifier_type' => ['sometimes', 'string'],
            'price_modifier_amount' => ['sometimes', 'integer'],
            'metadata' => ['nullable', 'array'],
            'is_default' => ['boolean'],
            'position' => ['integer'],
        ]);

        $value = $template->values()->create($data);

        return (new OptionTemplateValueResource($value))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(Request $request, OptionTemplate $template, OptionTemplateValue $value): OptionTemplateValueResource
    {
        $data = $request->validate([
            'label' => ['sometimes', 'string'],
            'value' => ['sometimes', 'string'],
            'price_modifier_type' => ['sometimes', 'string'],
            'price_modifier_amount' => ['sometimes', 'integer'],
            'metadata' => ['nullable', 'array'],
            'is_default' => ['boolean'],
            'position' => ['integer'],
        ]);

        $value->update($data);

        return new OptionTemplateValueResource($value);
    }

    public function destroy(OptionTemplate $template, OptionTemplateValue $value): JsonResponse
    {
        $value->delete();

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
