<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Presentation\Http\Controllers;

use App\Modules\Authentication\Application\Services\SocialAuthService;
use App\Modules\Authentication\Presentation\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class SocialAuthController extends Controller
{
    public function __construct(private readonly SocialAuthService $social)
    {
    }

    /**
     * Return the provider's OAuth authorization URL for the SPA to redirect to.
     */
    public function redirect(string $provider): JsonResponse
    {
        return response()->json([
            'redirect_url' => $this->social->redirectUrl($provider),
        ]);
    }

    /**
     * Handle the provider callback, logging in or provisioning the customer.
     */
    public function callback(string $provider): JsonResponse
    {
        $result = $this->social->authenticate($provider);

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }
}
