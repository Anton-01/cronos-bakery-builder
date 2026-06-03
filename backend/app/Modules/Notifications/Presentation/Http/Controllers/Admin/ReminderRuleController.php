<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Presentation\Http\Controllers\Admin;

use App\Modules\Notifications\Domain\Models\ReminderRule;
use App\Modules\Notifications\Presentation\Http\Requests\StoreReminderRuleRequest;
use App\Modules\Notifications\Presentation\Http\Resources\ReminderRuleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class ReminderRuleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ReminderRuleResource::collection(ReminderRule::query()->orderBy('offset_hours')->get());
    }

    public function store(StoreReminderRuleRequest $request): JsonResponse
    {
        $rule = ReminderRule::create($request->validated());

        return (new ReminderRuleResource($rule))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StoreReminderRuleRequest $request, string $rule): ReminderRuleResource
    {
        $model = ReminderRule::query()->findOrFail($rule);
        $model->update($request->validated());

        return new ReminderRuleResource($model->refresh());
    }

    public function destroy(string $rule): JsonResponse
    {
        ReminderRule::query()->findOrFail($rule)->delete();

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
