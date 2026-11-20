<?php

namespace App\Support;

use Throwable;

/**
 * Website languages, managed in Admin → Languages (add, edit, remove, show/hide).
 *
 * Stored in the `languages` setting as a list of {code, name, native, rtl, enabled}.
 * Until that setting exists, the list is built from config/cms.php (+ the older
 * `enabled_locales` / `custom_locales` settings), so existing installs keep their choices.
 *
 * At boot the list is written into config('cms.locales'), so every screen
 * (translation tabs, interface text, dashboard, API, website switcher) uses it.
 */
class Locales
{
    public const CODE_PATTERN = '/^[a-z]{2,3}(-[a-z0-9]{2,4})?$/';

    /** Built-in defaults from config/cms.php, captured before boot() replaces config('cms.locales'). */
    private static ?array $defaults = null;

    private static ?array $list = null;

    public static function defaults(): array
    {
        return self::$defaults ??= config('cms.locales', []);
    }

    /** code => ['name', 'native', 'rtl', 'enabled']; English is always first and always enabled. */
    public static function all(): array
    {
        if (self::$list !== null) {
            return self::$list;
        }

        $stored = Settings::get('languages');
        $list = [];
        if (is_array($stored) && $stored) {
            foreach ($stored as $l) {
                $code = strtolower((string) ($l['code'] ?? ''));
                if (preg_match(self::CODE_PATTERN, $code) && ! isset($list[$code])) {
                    $list[$code] = [
                        'name' => (string) ($l['name'] ?? $code),
                        'native' => (string) (($l['native'] ?? '') ?: ($l['name'] ?? $code)),
                        'rtl' => ! empty($l['rtl']),
                        'enabled' => ! empty($l['enabled']),
                        // false while the automatic translation of a new language has not finished
                        'translated' => ! array_key_exists('translated', $l) || ! empty($l['translated']),
                    ];
                }
            }
        } else {
            // Legacy / first run: built-in list + older settings
            $enabled = (array) Settings::get('enabled_locales', array_keys(self::defaults()));
            foreach (self::defaults() as $code => $l) {
                $list[$code] = ['name' => $l['name'], 'native' => $l['native'], 'rtl' => ! empty($l['rtl']), 'enabled' => in_array($code, $enabled, true), 'translated' => true];
            }
            foreach ((array) Settings::get('custom_locales', []) as $line) {
                $p = array_map('trim', explode('|', (string) $line));
                $code = strtolower($p[0] ?? '');
                if (preg_match(self::CODE_PATTERN, $code) && ($p[1] ?? '') !== '' && ! isset($list[$code])) {
                    $list[$code] = ['name' => $p[1], 'native' => ($p[2] ?? '') ?: $p[1], 'rtl' => strtolower($p[3] ?? '') === 'rtl', 'enabled' => in_array($code, $enabled, true), 'translated' => true];
                }
            }
        }

        // English is the master language: always present, first and enabled
        $en = $list['en'] ?? ['name' => 'English', 'native' => 'English', 'rtl' => false];
        unset($list['en']);
        $en['enabled'] = true;
        $en['translated'] = true;

        return self::$list = ['en' => $en] + $list;
    }

    public static function enabled(): array
    {
        if (! config('cms.multilingual')) {
            return ['en']; // multi-language is on hold: English only
        }

        return array_keys(array_filter(self::all(), fn ($l) => $l['enabled']));
    }

    /** Save the whole list (code => [...]) and refresh config. */
    public static function save(array $list): void
    {
        $rows = [];
        foreach ($list as $code => $l) {
            $rows[] = ['code' => $code, 'name' => $l['name'], 'native' => $l['native'], 'rtl' => (bool) $l['rtl'], 'enabled' => $code === 'en' || ! empty($l['enabled']), 'translated' => $code === 'en' || ($l['translated'] ?? true)];
        }
        Settings::set('languages', $rows);
        self::$list = null;
        self::boot();
    }

    /** Suggestions for the "Add a language" search (catalogue minus languages already added). */
    public static function catalog(): array
    {
        $have = self::all();
        $out = [];
        foreach (config('language_catalog') as $code => $c) {
            if (! isset($have[$code])) {
                $out[] = ['code' => $code, 'name' => $c[0], 'native' => $c[1], 'rtl' => ! empty($c[2])];
            }
        }

        return $out;
    }

    /** Put the managed list into config('cms.locales'). Safe before the database exists. */
    public static function boot(): void
    {
        self::defaults();
        try {
            $all = self::all();
        } catch (Throwable) {
            return; // e.g. during the first migration, when the settings table does not exist yet
        }
        config(['cms.locales' => array_map(fn ($l) => ['name' => $l['name'], 'native' => $l['native'], 'rtl' => $l['rtl']], $all)]);
    }

    public static function flush(): void
    {
        self::$list = null;
    }
}
