<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * The provider rejected the request with HTTP 429 (rate limit).
 */
class GatewayRateLimitException extends GatewayException
{
    public function __construct(
        string $message = '',
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        $response = response()->json(
            ['message' => $this->getMessage() ?: 'The payment provider is rate limiting requests. Please retry later.'],
            JsonResponse::HTTP_TOO_MANY_REQUESTS,
        );

        if ($this->retryAfterSeconds !== null) {
            $response->header('Retry-After', (string) $this->retryAfterSeconds);
        }

        return $response;
    }
}
