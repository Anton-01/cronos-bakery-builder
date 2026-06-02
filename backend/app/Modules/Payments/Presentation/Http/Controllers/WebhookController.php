<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Controllers;

use App\Modules\Payments\Application\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RuntimeException;

/**
 * Receives gateway webhooks. Signature verification is performed inside the
 * service; the raw request body is used so the HMAC matches byte-for-byte.
 */
class WebhookController extends Controller
{
    public function __construct(private readonly WebhookService $webhooks)
    {
    }

    public function handle(Request $request, string $gateway): JsonResponse
    {
        $headers = array_map(
            static fn (array $values): string => $values[0] ?? '',
            $request->headers->all(),
        );

        try {
            $result = $this->webhooks->handle($gateway, $request->getContent(), $headers);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return response()->json($result);
    }
}
