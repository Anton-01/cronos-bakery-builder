<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Modules\Administration\Application\Services\TwoFactorService;
use App\Modules\Administration\Domain\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Two-factor authentication enrolment for administrators.
 */
class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor)
    {
    }

    public function enable(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        return response()->json(['data' => $this->twoFactor->generate($admin)]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        /** @var Admin $admin */
        $admin = $request->user();
        $this->twoFactor->confirm($admin, $request->string('code')->value());

        return response()->json(['message' => 'Two-factor authentication enabled.']);
    }

    public function disable(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $this->twoFactor->disable($admin);

        return response()->json(['message' => 'Two-factor authentication disabled.']);
    }
}
