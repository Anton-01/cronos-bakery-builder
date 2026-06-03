<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Presentation\Http\Controllers\Admin;

use App\Modules\Notifications\Domain\Models\NotificationLog;
use App\Modules\Notifications\Presentation\Http\Resources\NotificationLogResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class NotificationLogController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return NotificationLogResource::collection(
            NotificationLog::query()->latest()->paginate(25),
        );
    }
}
