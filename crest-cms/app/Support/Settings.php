<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/** Read/write website settings stored in the `settings` table. */
class Settings
{
    private static ?array $cache = null;

    public static function all(): array
    {
        return self::$cache ??= Setting::query()->pluck('value', 'key')->all();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    /** Translatable setting value ({"en": ..}) for a locale, falling back to English. */
    public static function tr(string $key, string $locale, mixed $default = null): mixed
    {
        $value = self::get($key);
        if (! is_array($value) || array_is_list($value)) {
            return $value ?? $default;
        }
        foreach ([$locale, config('cms.fallback_locale', 'en')] as $loc) {
            if (isset($value[$loc]) && $value[$loc] !== '' && $value[$loc] !== []) {
                return $value[$loc];
            }
        }

        return $default;
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache = null;
    }

    /** Decrypt an encrypted secret (API keys). */
    public static function secret(string $key): ?string
    {
        $value = self::get($key);
        if (! is_string($value) || $value === '') {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return null;
        }
    }

    /** Website languages the visitor can choose (English always first). */
    public static function enabledLocales(): array
    {
        return Locales::enabled(); // managed in Admin → Languages
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
