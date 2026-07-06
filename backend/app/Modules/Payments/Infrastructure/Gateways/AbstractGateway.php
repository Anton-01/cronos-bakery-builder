<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Gateways;

use App\Modules\Payments\Application\DTO\WebhookEvent;
use App\Modules\Payments\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Domain\Enums\PaymentStatus;
use App\Modules\Payments\Domain\Exceptions\GatewayException;
use App\Modules\Payments\Domain\Exceptions\GatewayRateLimitException;
use App\Modules\Payments\Domain\Exceptions\GatewayTimeoutException;
use App\Modules\Payments\Domain\Exceptions\InvalidWebhookSignatureException;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Shared behaviour for gateway strategies:
 *
 * - Holds the configured gateway instance bound via {@see initialize()}.
 * - {@see callProvider()} wraps every outbound HTTP call with timeouts,
 *   bounded retries and typed exception mapping (timeout / rate limit /
 *   generic provider error) so the application layer handles third-party
 *   failures uniformly.
 * - {@see handleWebhook()} is a template method enforcing
 *   verify-signature-BEFORE-parse on every provider.
 */
abstract class AbstractGateway implements PaymentGatewayInterface
{
    protected ?PaymentGateway $gateway = null;

    public function initialize(PaymentGateway $gateway): static
    {
        $this->gateway = $gateway;

        return $this;
    }

    final public function handleWebhook(string $payload, array $headers): WebhookEvent
    {
        // Header names are normalised to lowercase by the controller.
        if (! $this->verifySignature($payload, $headers)) {
            throw new InvalidWebhookSignatureException();
        }

        return $this->parseWebhook($payload);
    }

    /**
     * @param  array<string, string>  $headers
     */
    abstract protected function verifySignature(string $payload, array $headers): bool;

    abstract protected function parseWebhook(string $payload): WebhookEvent;

    // --- Configured instance access ------------------------------------------

    protected function config(): PaymentGateway
    {
        return $this->gateway
            ?? throw new GatewayException('Gateway strategy used before initialize().');
    }

    protected function credential(string $key, mixed $default = null): mixed
    {
        return $this->config()->credential($key, $default);
    }

    protected function isProduction(): bool
    {
        return $this->config()->isProduction();
    }

    // --- Resilient HTTP against provider APIs ---------------------------------

    protected function http(): PendingRequest
    {
        return Http::timeout((int) config('payments.http.timeout', 15))
            ->connectTimeout((int) config('payments.http.connect_timeout', 5))
            ->retry(
                (int) config('payments.http.retries', 2),
                (int) config('payments.http.retry_delay_ms', 250),
                // Only retry transient failures: connection issues and 5xx.
                // 429 is NOT retried inline — it surfaces as a typed exception
                // so callers/queues can back off properly.
                when: static fn (\Throwable $e): bool => $e instanceof ConnectionException
                    || ($e instanceof RequestException && $e->response->serverError()),
                throw: false,
            )
            ->acceptJson();
    }

    /**
     * Execute a provider call mapping transport/HTTP failures to typed
     * domain exceptions.
     *
     * @param  callable(PendingRequest): Response  $call
     *
     * @throws GatewayTimeoutException|GatewayRateLimitException|GatewayException
     */
    protected function callProvider(callable $call): Response
    {
        try {
            $response = $call($this->http());
        } catch (ConnectionException $e) {
            throw new GatewayTimeoutException(
                "[{$this->driver()}] provider unreachable: {$e->getMessage()}",
            );
        }

        if ($response->status() === 429) {
            $retryAfter = $response->header('Retry-After');

            throw new GatewayRateLimitException(
                "[{$this->driver()}] provider rate limit exceeded.",
                is_numeric($retryAfter) ? (int) $retryAfter : null,
            );
        }

        if ($response->failed()) {
            throw new GatewayException(
                "[{$this->driver()}] provider error (HTTP {$response->status()}).",
            );
        }

        return $response;
    }

    // --- Crypto / parsing helpers ----------------------------------------------

    protected function hmac(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    protected function secureEquals(string $known, string $given): bool
    {
        return $known !== '' && hash_equals($known, $given);
    }

    /**
     * Decode a JSON webhook body to an array.
     *
     * @return array<string, mixed>
     */
    protected function decode(string $payload): array
    {
        return json_decode($payload, true) ?: [];
    }

    protected function mapStatus(string $value): PaymentStatus
    {
        return match ($value) {
            'paid', 'approved', 'succeeded', 'completed' => PaymentStatus::Paid,
            'failed', 'rejected', 'declined' => PaymentStatus::Failed,
            'refunded' => PaymentStatus::Refunded,
            'cancelled', 'canceled' => PaymentStatus::Cancelled,
            'processing', 'in_process' => PaymentStatus::Processing,
            default => PaymentStatus::Pending,
        };
    }

    /**
     * Stable event id for idempotency when the provider does not send one:
     * a hash of the raw body identifies exact duplicate deliveries.
     */
    protected function fallbackEventId(string $payload): string
    {
        return 'sha256:' . hash('sha256', $payload);
    }
}
