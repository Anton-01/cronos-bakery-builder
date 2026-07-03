<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'versionable_type',
        'versionable_id',
        'version_number',
        'payload_before',
        'payload_after',
        'status_before',
        'status_after',
        'change_summary',
        'author_id',
    ];

    protected $casts = [
        'payload_before' => 'array',
        'payload_after' => 'array',
        'version_number' => 'integer',
        'created_at' => 'datetime',
    ];

    /** @return MorphTo<Model, $this> */
    public function versionable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<\App\Modules\Administration\Domain\Models\Admin, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Administration\Domain\Models\Admin::class, 'author_id');
    }
}
