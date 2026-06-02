<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Modules\Orders\Domain\Models\Branch;
use App\Modules\Orders\Presentation\Http\Resources\BranchResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class BranchController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BranchResource::collection(
            Branch::query()->active()->orderBy('position')->orderBy('name')->get(),
        );
    }
}
