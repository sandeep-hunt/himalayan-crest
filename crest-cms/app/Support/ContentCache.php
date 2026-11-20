<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache for public API responses. Any content change bumps the version,
 * so every cached response is invalidated at once without needing cache tags.
 */
class ContentCache
{
    private const KEY = 'cms_content_version';

    public static function version(): int
    {
        return (int) Cache::get(self::KEY, 1);
    }

    public static function bump(): void
    {
        Cache::forever(self::KEY, self::version() + 1);
    }

    public static function remember(string $key, Closure $callback): mixed
    {
        return Cache::remember('cms:v'.self::version().':'.$key, now()->addHours(6), $callback);
    }
}
