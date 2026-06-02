<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Http\Controllers;

use App\Modules\Calendar\Application\Services\CalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

/**
 * Public availability lookup consumed by the storefront when scheduling a
 * delivery or pickup.
 */
class AvailabilityController extends Controller
{
    public function __construct(private readonly CalendarService $calendar)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $slug = $request->query('product');
        $productId = is_string($slug) ? $this->calendar->resolveProductId($slug) : null;

        $from = $request->filled('from')
            ? Carbon::parse((string) $request->query('from'))
            : null;
        $days = (int) min(60, max(1, (int) $request->query('days', 30)));

        return response()->json([
            'data' => [
                'product' => $slug,
                'lead_time_hours' => $this->calendar->leadTimeHours($productId),
                'minimum' => $this->calendar->minimumDate($productId),
                'days' => $this->calendar->availability($productId, $from, $days),
            ],
        ]);
    }
}
