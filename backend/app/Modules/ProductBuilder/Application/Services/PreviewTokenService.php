<?php

declare(strict_types=1);

namespace App\Modules\ProductBuilder\Application\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Mints and resolves opaque, short-lived preview tokens that authorize viewing
 * a product on the public storefront regardless of its publication status.
 * The token is the only credential: no admin session is required in the tab
 * that consumes it, and it expires automatically.
 */
final class PreviewTokenService
{
    private const CACHE_PREFIX = 'product_preview:';

    public const TTL_MINUTES = 30;

    public function mint(string $productId): string
    {
        $token = Str::random(64);
        Cache::put(self::CACHE_PREFIX.$token, $productId, now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    /** Product id the token grants access to, or null if expired/invalid. */
    public function resolve(?string $token): ?string
    {
        if ($token === null || $token === '') {
            return null;
        }

        $productId = Cache::get(self::CACHE_PREFIX.$token);

        return is_string($productId) ? $productId : null;
    }
}
