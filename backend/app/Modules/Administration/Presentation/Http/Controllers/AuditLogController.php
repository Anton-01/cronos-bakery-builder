<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Modules\Administration\Domain\Models\AuditLog;
use App\Modules\Administration\Presentation\Http\Resources\AuditLogResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AuditLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = AuditLog::query()
            ->when($request->filled('admin_id'), fn ($q) => $q->where('admin_id', $request->query('admin_id')))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->query('method')))
            ->latest('created_at')
            ->paginate(30);

        return AuditLogResource::collection($logs);
    }
}
