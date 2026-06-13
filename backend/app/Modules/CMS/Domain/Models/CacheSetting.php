<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class CacheSetting extends Model
{
    protected $fillable = [
        'tag',
        'ttl_seconds',
        'last_flushed_at',
    ];

    protected $casts = [
        'ttl_seconds' => 'integer',
        'last_flushed_at' => 'datetime',
    ];
}
