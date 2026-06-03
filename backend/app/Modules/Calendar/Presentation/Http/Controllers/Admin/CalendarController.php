<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Http\Controllers\Admin;

use App\Modules\Calendar\Application\Services\CalendarAdminService;
use App\Modules\Calendar\Domain\Models\Holiday;
use App\Modules\Calendar\Domain\Models\ScheduleDay;
use App\Modules\Calendar\Domain\Models\TimeSlot;
use App\Modules\Calendar\Presentation\Http\Requests\SetProductionRuleRequest;
use App\Modules\Calendar\Presentation\Http\Requests\StoreBlackoutRequest;
use App\Modules\Calendar\Presentation\Http\Requests\StoreHolidayRequest;
use App\Modules\Calendar\Presentation\Http\Requests\StoreTimeSlotRequest;
use App\Modules\Calendar\Presentation\Http\Requests\UpdateScheduleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Administrative configuration of the scheduling engine.
 */
class CalendarController extends Controller
{
    public function __construct(private readonly CalendarAdminService $service)
    {
    }

    public function schedule(): JsonResponse
    {
        return response()->json(['data' => ScheduleDay::query()->orderBy('weekday')->get()]);
    }

    public function updateSchedule(UpdateScheduleRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->service->updateSchedule($request->validated('days'))]);
    }

    public function slots(): JsonResponse
    {
        return response()->json(['data' => TimeSlot::query()->orderBy('position')->get()]);
    }

    public function storeSlot(StoreTimeSlotRequest $request): JsonResponse
    {
        return response()->json(
            ['data' => $this->service->createSlot($request->validated())],
            JsonResponse::HTTP_CREATED,
        );
    }

    public function updateSlot(StoreTimeSlotRequest $request, string $slot): JsonResponse
    {
        return response()->json(['data' => $this->service->updateSlot($slot, $request->validated())]);
    }

    public function destroySlot(string $slot): JsonResponse
    {
        $this->service->deleteSlot($slot);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }

    public function holidays(): JsonResponse
    {
        return response()->json(['data' => Holiday::query()->orderBy('date')->get()]);
    }

    public function storeHoliday(StoreHolidayRequest $request): JsonResponse
    {
        return response()->json(
            ['data' => $this->service->createHoliday($request->validated())],
            JsonResponse::HTTP_CREATED,
        );
    }

    public function destroyHoliday(string $holiday): JsonResponse
    {
        $this->service->deleteHoliday($holiday);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }

    public function storeBlackout(StoreBlackoutRequest $request): JsonResponse
    {
        return response()->json(
            ['data' => $this->service->createBlackout($request->validated())],
            JsonResponse::HTTP_CREATED,
        );
    }

    public function destroyBlackout(string $blackout): JsonResponse
    {
        $this->service->deleteBlackout($blackout);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }

    public function setProductionRule(SetProductionRuleRequest $request): JsonResponse
    {
        $rule = $this->service->setProductionRule(
            $request->validated('product_id'),
            (int) $request->validated('lead_time_hours'),
        );

        return response()->json(['data' => $rule]);
    }
}
