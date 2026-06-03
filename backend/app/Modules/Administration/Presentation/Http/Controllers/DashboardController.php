<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Modules\Administration\Application\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $from = $request->filled('from') ? Carbon::parse((string) $request->query('from'))->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse((string) $request->query('to'))->endOfDay() : null;

        return response()->json(['data' => $this->analytics->dashboard($from, $to)]);
    }
}
