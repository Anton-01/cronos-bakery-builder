<?php

declare(strict_types=1);

namespace App\Modules\CMS\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StorageProvider extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'driver',
        'credentials',
        'bucket',
        'region',
        'endpoint',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function toDiskConfig(): array
    {
        $base = [
            'driver' => $this->driver,
            'bucket' => $this->bucket,
            'region' => $this->region,
        ];

        $credentials = $this->credentials;

        return match ($this->driver) {
            's3' => array_merge($base, [
                'key' => $credentials['key'] ?? null,
                'secret' => $credentials['secret'] ?? null,
                'endpoint' => $this->endpoint,
                'use_path_style_endpoint' => $credentials['use_path_style'] ?? false,
            ]),
            'gcs' => array_merge($base, [
                'driver' => 'gcs',
                'project_id' => $credentials['project_id'] ?? null,
                'key_file' => $credentials['key_file'] ?? null,
            ]),
            default => $base,
        };
    }
}
