<?php

declare(strict_types=1);

namespace App\Shared\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

class DevLogController extends Controller
{
    public function latest(Request $request): JsonResponse
    {
        $lines = (int) $request->query('lines', '100');
        $lines = min($lines, 500);

        $logFile = storage_path('logs/laravel.log');

        if (! File::exists($logFile)) {
            return response()->json(['lines' => [], 'message' => 'No log file found.']);
        }

        $content = File::get($logFile);
        $allLines = explode("\n", trim($content));
        $tail = array_slice($allLines, -$lines);

        return response()->json([
            'file' => 'laravel.log',
            'total_lines' => count($allLines),
            'showing' => count($tail),
            'lines' => $tail,
        ]);
    }

    public function clear(): JsonResponse
    {
        $logFile = storage_path('logs/laravel.log');

        if (File::exists($logFile)) {
            File::put($logFile, '');
        }

        return response()->json(['message' => 'Log file cleared.']);
    }
}
