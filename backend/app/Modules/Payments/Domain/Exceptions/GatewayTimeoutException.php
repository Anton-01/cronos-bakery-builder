<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * The provider did not answer within the configured timeout window.
 */
class GatewayTimeoutException extends GatewayException
{
    public function render(): JsonResponse
    {
        return response()->json(
            ['message' => $this->getMessage() ?: 'The payment provider timed out. Please retry.'],
            JsonResponse::HTTP_GATEWAY_TIMEOUT,
        );
    }
}
