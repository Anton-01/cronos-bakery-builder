<?php

declare(strict_types=1);

namespace App\Modules\Payments\Presentation\Http\Resources;

use App\Modules\Payments\Domain\Models\GatewayConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GatewayConfig
 *
 * Credentials are never exposed; only whether they are configured.
 */
class GatewayConfigResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gateway' => $this->gateway->value,
            'gateway_label' => $this->gateway->label(),
            'mode' => $this->mode->value,
            'is_active' => $this->is_active,
            'has_credentials' => ! empty($this->credentials),
            'has_webhook_secret' => $this->webhookSecret() !== '',
        ];
    }
}
