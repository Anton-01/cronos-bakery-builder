<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Controllers;

use App\Modules\Payments\Application\Services\WebhookService;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Generic webhook endpoint: POST payments/webhooks/{driver}/{gateway}.
 *
 * The gateway instance is resolved from the URL (each configured instance has
 * its own webhook URL, so the right per-brand secret is always used). The raw
 * request body is passed through untouched so HMACs match byte-for-byte.
 * Signature verification and idempotency live in WebhookService / strategy —
 * an InvalidWebhookSignatureException renders as 400 via its own renderer.
 */
class WebhookController extends Controller
{
    public function __construct(private readonly WebhookService $webhooks)
    {
    }

    public function handle(Request $request, string $driver, int $gateway): JsonResponse
    {
        $model = PaymentGateway::query()
            ->active()
            ->where('driver_name', $driver)
            ->whereKey($gateway)
            ->firstOr(fn () => throw new NotFoundHttpException('Gateway not configured.'));

        $headers = array_map(
            static fn (array $values): string => $values[0] ?? '',
            $request->headers->all(),
        );

        $result = $this->webhooks->handle($model, $request->getContent(), $headers);

        return response()->json($result);
    }
}
