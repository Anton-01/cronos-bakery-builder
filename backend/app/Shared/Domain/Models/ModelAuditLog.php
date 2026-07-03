<?php

declare(strict_types=1);

namespace App\Shared\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One entry in the model-level audit trail: an attribute-by-attribute diff of
 * a single lifecycle event (created / updated / deleted / restored) on any
 * model using the {@see \App\Shared\Domain\Concerns\Auditable} trait.
 *
 * Not to be confused with `audit_logs` (Administration), which records the
 * HTTP requests of the admin panel.
 *
 * @property int $id
 * @property int|null $brand_id
 * @property int|null $user_id
 * @property string $event
 * @property string $auditable_type
 * @property int $auditable_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $ip_address
 */
class ModelAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'model_audit_logs';

    protected $fillable = [
        'brand_id',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'brand_id' => 'integer',
        'user_id' => 'integer',
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
