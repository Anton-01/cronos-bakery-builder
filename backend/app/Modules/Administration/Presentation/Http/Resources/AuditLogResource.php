<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Resources;

use App\Modules\Administration\Domain\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuditLog
 */
class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admin_id' => $this->admin_id,
            'admin_name' => $this->admin_name,
            'method' => $this->method,
            'path' => $this->path,
            'route_name' => $this->route_name,
            'status_code' => $this->status_code,
            'ip_address' => $this->ip_address,
            'payload' => $this->payload,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
