<?php

declare(strict_types=1);

namespace App\Shared\Http\Resources;

use App\Shared\Domain\Models\PersonalAccessToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PersonalAccessToken
 *
 * A Sanctum token presented as a "session/device". `is_current` marks the
 * token performing the request so the UI can protect it from self-revocation.
 */
class SessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $current = $request->user()?->currentAccessToken();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'device_name' => $this->device_name ?? PersonalAccessToken::deviceNameFrom($this->user_agent),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'is_current' => $current !== null && (int) $current->id === (int) $this->id,
        ];
    }
}
