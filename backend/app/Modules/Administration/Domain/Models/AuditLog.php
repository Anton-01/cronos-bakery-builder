<?php

declare(strict_types=1);

namespace App\Modules\Administration\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An immutable record of a mutating administrative action.
 *
 * @property int $id
 * @property int|null $admin_id
 * @property string|null $admin_name
 * @property string $method
 * @property string $path
 * @property int $status_code
 * @property array<string, mixed>|null $payload
 */
class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'admin_name',
        'method',
        'path',
        'route_name',
        'status_code',
        'ip_address',
        'user_agent',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'status_code' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
