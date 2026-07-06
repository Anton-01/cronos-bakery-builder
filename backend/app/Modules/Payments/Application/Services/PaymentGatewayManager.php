<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Services;

use App\Modules\Payments\Domain\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Domain\Exceptions\UnsupportedDriverException;
use App\Modules\Payments\Domain\Models\PaymentGateway;
use Illuminate\Contracts\Foundation\Application;

/**
 * Resolves the {@see PaymentGatewayInterface} strategy for a driver_name from
 * the config/payments.php registry. Fully dynamic: adding a provider is one
 * config entry + one class — no conditionals anywhere in controllers/services.
 */
final class PaymentGatewayManager
{
    public function __construct(private readonly Application $app)
    {
    }

    /**
     * Resolve the raw (uninitialised) strategy for a driver.
     */
    public function driver(string $driverName): PaymentGatewayInterface
    {
        $class = config("payments.drivers.{$driverName}.class");

        if (! is_string($class)) {
            throw UnsupportedDriverException::for($driverName);
        }

        $strategy = $this->app->make($class);

        if (! $strategy instanceof PaymentGatewayInterface) {
            throw UnsupportedDriverException::for($driverName);
        }

        return $strategy;
    }

    /**
     * Resolve the strategy for a configured gateway instance, initialised with
     * its credentials and environment. This is the entry point used by
     * controllers and services.
     */
    public function forGateway(PaymentGateway $gateway): PaymentGatewayInterface
    {
        return $this->driver($gateway->driver_name)->initialize($gateway);
    }

    /**
     * Driver metadata for the admin UI (labels + dynamic credential fields).
     *
     * @return array<int, array{driver_name: string, label: string, fields: array<int, array<string, mixed>>}>
     */
    public function supportedDrivers(): array
    {
        $drivers = [];
        foreach ((array) config('payments.drivers', []) as $name => $definition) {
            $drivers[] = [
                'driver_name' => (string) $name,
                'label' => (string) ($definition['label'] ?? $name),
                'fields' => (array) ($definition['credentials'] ?? []),
            ];
        }

        return $drivers;
    }

    public function supports(string $driverName): bool
    {
        return is_string(config("payments.drivers.{$driverName}.class"));
    }
}
