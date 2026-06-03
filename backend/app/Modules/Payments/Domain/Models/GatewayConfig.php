<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Models;

use App\Modules\Payments\Domain\Enums\GatewayType;
use App\Modules\Payments\Domain\Enums\PaymentMode;
use App\Modules\Payments\Infrastructure\Database\Factories\GatewayConfigFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed configuration for a payment provider, including its operating
 * mode and (encrypted) credentials.
 *
 * @property string $id
 * @property GatewayType $gateway
 * @property PaymentMode $mode
 * @property array<string, mixed>|null $credentials
 * @property bool $is_active
 */
class GatewayConfig extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'payment_gateway_configs';

    protected $fillable = ['gateway', 'mode', 'credentials', 'is_active'];

    protected $casts = [
        'gateway' => GatewayType::class,
        'mode' => PaymentMode::class,
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
    ];

    public function credential(string $key, mixed $default = null): mixed
    {
        return ($this->credentials ?? [])[$key] ?? $default;
    }

    /**
     * The webhook signing secret for the active mode.
     */
    public function webhookSecret(): string
    {
        return (string) $this->credential('webhook_secret', '');
    }

    protected static function newFactory(): GatewayConfigFactory
    {
        return GatewayConfigFactory::new();
    }
}
