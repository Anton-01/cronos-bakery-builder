<?php

declare(strict_types=1);

namespace App\Modules\Authentication\Presentation\Http\Resources;

use App\Modules\Authentication\Domain\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'email_verified' => $this->hasVerifiedEmail(),
            'roles' => [$this->role->value],
            'is_suspended' => $this->is_suspended,
            'suspended_at' => $this->suspended_at,
            'suspended_until' => $this->suspended_until,
            'suspension_reason' => $this->suspension_reason,
            'created_at' => $this->created_at,
        ];
    }
}
