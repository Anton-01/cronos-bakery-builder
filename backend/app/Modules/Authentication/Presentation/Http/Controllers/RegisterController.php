<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Presentation\Http\Controllers;

use App\Modules\Authentication\Application\DTO\RegisterData;
use App\Modules\Authentication\Application\Services\RegistrationService;
use App\Modules\Authentication\Presentation\Http\Requests\RegisterRequest;
use App\Modules\Authentication\Presentation\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class RegisterController extends Controller
{
    public function __construct(private readonly RegistrationService $registration)
    {
    }

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $result = $this->registration->register(RegisterData::fromArray($request->validated()));

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], JsonResponse::HTTP_CREATED);
    }
}
