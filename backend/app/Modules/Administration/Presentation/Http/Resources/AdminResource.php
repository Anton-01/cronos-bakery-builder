<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Resources;

use App\Modules\Administration\Domain\Models\Admin;
use App\Shared\Application\Services\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Admin
 */
class AdminResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => app(AvatarService::class)->urlFor($this->resource),
            'notification_settings' => $this->notification_settings ?? (object) [],
            'two_factor_enabled' => $this->hasTwoFactorEnabled(),
            'is_active' => $this->is_active,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
        ];
    }
}
