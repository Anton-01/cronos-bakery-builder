<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Models;

use App\Modules\CMS\Domain\Models\Brand;
use App\Modules\Payments\Domain\Enums\GatewayEnvironment;
use App\Modules\Payments\Infrastructure\Casts\EncryptedCredentials;
use App\Modules\Payments\Infrastructure\Database\Factories\PaymentGatewayFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A configured payment gateway instance for a brand (tenant). Credentials are
 * encrypted at rest per value (EncryptedCredentials cast) and are NEVER
 * exposed in plaintext by the API (see PaymentGatewayResource::maskedCredentials).
 *
 * @property int $id
 * @property int|null $brand_id
 * @property string $driver_name
 * @property string $name
 * @property GatewayEnvironment $environment
 * @property bool $is_active
 * @property array<string, string|null>|null $credentials
 */
class PaymentGateway extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'payment_gateways';

    protected $fillable = [
        'brand_id',
        'driver_name',
        'name',
        'environment',
        'is_active',
        'credentials',
    ];

    // Defence in depth: even a naive ->toArray()/json_encode of the model
    // never serialises credentials.
    protected $hidden = ['credentials'];

    protected $casts = [
        'brand_id' => 'integer',
        'environment' => GatewayEnvironment::class,
        'is_active' => 'boolean',
        'credentials' => EncryptedCredentials::class,
    ];

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Rows visible to a brand: its own gateways plus shared (brand-less) ones.
     *
     * @param  Builder<self>  $query
     */
    public function scopeForBrand(Builder $query, ?int $brandId): void
    {
        if ($brandId === null) {
            return;
        }

        $query->where(fn (Builder $q) => $q->where('brand_id', $brandId)->orWhereNull('brand_id'));
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return ($this->credentials ?? [])[$key] ?? $default;
    }

    /**
     * The webhook signing secret for this gateway instance.
     */
    public function webhookSecret(): string
    {
        return (string) $this->credential('webhook_secret', '');
    }

    public function isProduction(): bool
    {
        return $this->environment === GatewayEnvironment::Production;
    }

    public function driverLabel(): string
    {
        return (string) config("payments.drivers.{$this->driver_name}.label", $this->driver_name);
    }

    /**
     * Masked view of the credentials, safe to return through the API: for each
     * configured key only a hint (last 4 characters) is exposed.
     *
     * @return array<string, string|null>
     */
    public function maskedCredentials(): array
    {
        $masked = [];
        foreach ($this->credentials ?? [] as $key => $value) {
            if ($value === null || $value === '') {
                $masked[$key] = null;

                continue;
            }
            $hint = strlen($value) > 8 ? substr($value, -4) : '';
            $masked[$key] = '••••••••' . $hint;
        }

        return $masked;
    }

    protected static function newFactory(): PaymentGatewayFactory
    {
        return PaymentGatewayFactory::new();
    }
}
