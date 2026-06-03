<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Presentation\Http\Controllers\Admin;

use App\Modules\Notifications\Domain\Models\NotificationTemplate;
use App\Modules\Notifications\Presentation\Http\Requests\StoreTemplateRequest;
use App\Modules\Notifications\Presentation\Http\Resources\NotificationTemplateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class TemplateController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return NotificationTemplateResource::collection(
            NotificationTemplate::query()->orderBy('event')->get(),
        );
    }

    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $template = NotificationTemplate::create($request->validated());

        return (new NotificationTemplateResource($template))
            ->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StoreTemplateRequest $request, string $template): NotificationTemplateResource
    {
        $model = NotificationTemplate::query()->findOrFail($template);
        $model->update($request->validated());

        return new NotificationTemplateResource($model->refresh());
    }

    public function destroy(string $template): JsonResponse
    {
        NotificationTemplate::query()->findOrFail($template)->delete();

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
