<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * The webhook payload's cryptographic signature did not match. The event is
 * rejected before any state change.
 */
class InvalidWebhookSignatureException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(
            ['message' => 'Invalid webhook signature.'],
            JsonResponse::HTTP_BAD_REQUEST,
        );
    }
}
