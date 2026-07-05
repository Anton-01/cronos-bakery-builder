<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Base error for any failure while talking to a payment provider. Renders as
 * 502 so upstream provider failures are never confused with our own 5xx.
 */
class GatewayException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(
            ['message' => $this->getMessage() ?: 'Payment provider error.'],
            JsonResponse::HTTP_BAD_GATEWAY,
        );
    }
}
