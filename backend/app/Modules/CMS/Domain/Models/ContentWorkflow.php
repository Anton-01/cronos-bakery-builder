<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use App\Modules\CMS\Domain\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentWorkflow extends Model
{
    use HasUuids;

    protected $fillable = [
        'workflowable_type',
        'workflowable_id',
        'from_status',
        'to_status',
        'requested_by',
        'approved_by',
        'comment',
        'scheduled_at',
    ];

    protected $casts = [
        'from_status' => ContentStatus::class,
        'to_status' => ContentStatus::class,
        'scheduled_at' => 'datetime',
    ];

    /** @return MorphTo<Model, $this> */
    public function workflowable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<\App\Modules\Authentication\Domain\Models\User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Authentication\Domain\Models\User::class, 'requested_by');
    }

    /** @return BelongsTo<\App\Modules\Authentication\Domain\Models\User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Authentication\Domain\Models\User::class, 'approved_by');
    }
}
