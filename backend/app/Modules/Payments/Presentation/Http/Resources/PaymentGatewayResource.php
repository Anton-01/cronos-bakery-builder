<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Resources;

use App\Modules\Payments\Domain\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PaymentGateway
 *
 * Credentials are NEVER returned in plaintext: only masked hints
 * ("••••••••1234") so the admin UI can show which fields are configured.
 */
class PaymentGatewayResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'driver_name' => $this->driver_name,
            'driver_label' => $this->driverLabel(),
            'name' => $this->name,
            'environment' => $this->environment->value,
            'is_active' => $this->is_active,
            'credentials' => $this->maskedCredentials(),
            'has_webhook_secret' => $this->webhookSecret() !== '',
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
