<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Turns the field definitions in config/cms.php into validation rules and
 * stored values. Used by resources, homepage sections and settings alike.
 *
 * Input naming (dot notation, rendered as brackets in HTML):
 *   translatable  name.en, name.es, ...      lines: one item per line in a textarea
 *   image         name (existing path), name_file (upload), name_remove (checkbox)
 *   gallery       name_keep[] (existing paths), name_files[] (uploads)
 *   group         prefix.name.0.subfield ...
 */
class Fields
{
    public static function locales(): array
    {
        return array_keys(config('cms.locales'));
    }

    public static function htmlName(string $dotted): string
    {
        $parts = explode('.', $dotted);

        return array_shift($parts).implode('', array_map(fn ($p) => "[$p]", $parts));
    }

    /** Validation rules for a set of fields. */
    public static function rules(array $fields, string $prefix = ''): array
    {
        $rules = [];
        foreach ($fields as $f) {
            $key = ltrim($prefix.'.'.$f['name'], '.');
            $required = ! empty($f['required']);
            $extra = $f['rules'] ?? null;
            $tr = ! empty($f['tr']);

            switch ($f['type']) {
                case 'group':
                    for ($i = 0; $i < ($f['count'] ?? 1); $i++) {
                        $rules += self::rules($f['fields'], "$key.$i");
                    }
                    break;
                case 'image':
                    $rules[$key.'_file'] = 'nullable|'.Media::RULE;
                    break;
                case 'gallery':
                    $rules[$key.'_files'] = 'nullable|array|max:20';
                    $rules[$key.'_files.*'] = Media::RULE;
                    $rules[$key.'_keep'] = 'nullable|array';
                    break;
                case 'checkbox':
                    $rules[$key] = 'nullable|boolean';
                    break;
                case 'multicheck':
                case 'locales':
                    $rules[$key] = 'nullable|array';
                    $rules[$key.'.*'] = 'string|max:20';
                    break;
                case 'number':
                    $rules[$key] = ($required ? 'required' : 'nullable').'|integer'.($extra ? '|'.$extra : '');
                    break;
                case 'date':
                    $rules[$key] = ($required ? 'required' : 'nullable').'|date';
                    break;
                case 'trek':
                    $rules[$key] = ($required ? 'required' : 'nullable').'|integer|exists:treks,id';
                    break;
                case 'menuparent':
                    $rules[$key] = 'nullable|integer|exists:menu_items,id';
                    break;
                case 'select':
                    $in = 'in:'.implode(',', array_keys($f['options']));
                    $rules[$key] = ($required ? 'required' : 'nullable').'|string|'.$in;
                    break;
                case 'secret':
                    $rules[$key] = 'nullable|string|max:300';
                    break;
                default: // text, textarea, markdown, lines
                    $max = in_array($f['type'], ['textarea', 'markdown', 'lines'], true) ? 60000 : 500;
                    if ($tr) {
                        $rules[$key] = 'nullable|array';
                        $rules[$key.'.en'] = ($required ? 'required' : 'nullable')."|string|max:$max";
                        $rules[$key.'.*'] = "nullable|string|max:$max";
                    } else {
                        $rules[$key] = ($required ? 'required' : 'nullable')."|string|max:$max".($extra ? '|'.$extra : '');
                    }
            }
        }

        return $rules;
    }

    /** Friendly attribute names for validation messages ("name.en" -> "Trek name (English)"). */
    public static function attributeNames(array $fields, string $prefix = '', string $labelPrefix = ''): array
    {
        $names = [];
        foreach ($fields as $f) {
            $key = ltrim($prefix.'.'.$f['name'], '.');
            $label = $labelPrefix.$f['label'];
            if ($f['type'] === 'group') {
                for ($i = 0; $i < ($f['count'] ?? 1); $i++) {
                    $names += self::attributeNames($f['fields'], "$key.$i", $label.' #'.($i + 1).' – ');
                }
                continue;
            }
            $names[$key] = $label;
            $names[$key.'_file'] = $label;
            $names[$key.'_files.*'] = $label;
            foreach (config('cms.locales') as $code => $l) {
                $names["$key.$code"] = "$label ({$l['name']})";
            }
        }

        return $names;
    }

    /**
     * Build stored values from the request.
     * $old holds current stored values (for images, galleries and secrets that are kept).
     */
    public static function values(array $fields, Request $request, string $prefix, array $old, string $folder): array
    {
        $out = [];
        foreach ($fields as $f) {
            $name = $f['name'];
            $key = ltrim($prefix.'.'.$name, '.');
            $tr = ! empty($f['tr']);
            $current = $old[$name] ?? null;

            switch ($f['type']) {
                case 'group':
                    $items = [];
                    for ($i = 0; $i < ($f['count'] ?? 1); $i++) {
                        $items[] = self::values($f['fields'], $request, "$key.$i", $current[$i] ?? [], $folder);
                    }
                    $out[$name] = $items;
                    break;

                case 'image':
                    $value = $current;
                    if ($request->boolean($key.'_remove')) {
                        Media::delete($value);
                        $value = null;
                    }
                    if ($file = $request->file($key.'_file')) {
                        Media::delete($value);
                        $value = Media::store($file, $folder);
                    }
                    $out[$name] = $value;
                    break;

                case 'gallery':
                    $existing = is_array($current) ? $current : [];
                    $keep = array_values(array_intersect((array) $request->input($key.'_keep', []), $existing));
                    foreach (array_diff($existing, $keep) as $removed) {
                        Media::delete($removed);
                    }
                    foreach ((array) $request->file($key.'_files', []) as $file) {
                        $keep[] = Media::store($file, $folder);
                    }
                    $out[$name] = $keep;
                    break;

                case 'checkbox':
                    $out[$name] = $request->boolean($key);
                    break;

                case 'number':
                case 'trek':
                case 'menuparent':
                    $v = $request->input($key);
                    $out[$name] = $v === null || $v === '' ? ($name === 'sort_order' ? 0 : null) : (int) $v;
                    break;

                case 'multicheck':
                    $allowed = array_map('strval', array_keys($f['options']));
                    $out[$name] = array_values(array_intersect($allowed, array_map('strval', (array) $request->input($key, []))));
                    break;

                case 'locales':
                    $chosen = array_values(array_intersect(self::locales(), (array) $request->input($key, [])));
                    if (! in_array('en', $chosen, true)) {
                        array_unshift($chosen, 'en');
                    }
                    $out[$name] = $chosen;
                    break;

                case 'secret':
                    $v = trim((string) $request->input($key, ''));
                    if ($request->boolean($key.'_clear')) {
                        $out[$name] = null;
                    } else {
                        $out[$name] = $v !== '' ? Crypt::encryptString($v) : $current;
                    }
                    break;

                case 'lines':
                    if ($tr) {
                        $vals = [];
                        foreach (self::locales() as $loc) {
                            $lines = self::splitLines($request->input("$key.$loc"));
                            if ($lines) {
                                $vals[$loc] = $lines;
                            }
                        }
                        $out[$name] = $vals ?: null;
                    } else {
                        $out[$name] = self::splitLines($request->input($key));
                    }
                    break;

                default: // text, textarea, markdown, select, date
                    if ($tr) {
                        $vals = [];
                        foreach (self::locales() as $loc) {
                            $v = trim(self::normaliseNewlines((string) $request->input("$key.$loc", '')));
                            if ($v !== '') {
                                $vals[$loc] = $v;
                            }
                        }
                        $out[$name] = $vals ?: null;
                    } else {
                        $v = $request->input($key);
                        $v = is_string($v) ? trim(self::normaliseNewlines($v)) : $v;
                        $out[$name] = $v === '' ? null : $v;
                    }
            }
        }

        return $out;
    }

    /** Text typed in a browser on Windows uses CR LF line breaks; store plain LF. */
    public static function normaliseNewlines(string $s): string
    {
        return str_replace(["\r\n", "\r"], "\n", $s);
    }

    public static function splitLines(mixed $text): array
    {
        if (is_array($text)) {
            return array_values(array_filter(array_map('trim', $text), fn ($l) => $l !== ''));
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text)), fn ($l) => $l !== ''));
    }

    /** Value for a form input: translatable fields return the locale entry; lines are joined with newlines. */
    public static function display(array $f, mixed $value, ?string $locale = null): mixed
    {
        if (! empty($f['tr']) && $locale !== null) {
            $value = is_array($value) ? ($value[$locale] ?? null) : ($locale === 'en' ? $value : null);
        }
        if ($f['type'] === 'lines') {
            return is_array($value) ? implode("\n", $value) : (string) $value;
        }

        return $value;
    }

    /** Resolve a stored field value for the public API (locale + image URLs). */
    public static function resolve(array $f, mixed $value, string $locale): mixed
    {
        $fallback = config('cms.fallback_locale', 'en');
        if ($f['type'] === 'group') {
            return array_map(fn ($item) => self::resolveAll($f['fields'], (array) $item, $locale), (array) $value);
        }
        if (! empty($f['tr'])) {
            if (is_array($value) && ! array_is_list($value)) {
                $v = $value[$locale] ?? null;
                $value = ($v === null || $v === '' || $v === []) ? ($value[$fallback] ?? null) : $v;
            }
        }
        if ($f['type'] === 'image') {
            return Media::url($value);
        }
        if ($f['type'] === 'gallery') {
            return array_map([Media::class, 'url'], (array) $value);
        }
        if ($f['type'] === 'lines') {
            return array_values((array) ($value ?? []));
        }

        return $value;
    }

    public static function resolveAll(array $fields, array $values, string $locale): array
    {
        $out = [];
        foreach ($fields as $f) {
            if (in_array($f['type'], ['secret'], true)) {
                continue;
            }
            $out[$f['name']] = self::resolve($f, $values[$f['name']] ?? null, $locale);
        }

        return $out;
    }

    /** Percentage of translatable values filled in for a locale (for the dashboard). */
    public static function completeness(array $fields, array $values, string $locale): array
    {
        $total = 0;
        $done = 0;
        foreach ($fields as $f) {
            if ($f['type'] === 'group') {
                foreach ((array) ($values[$f['name']] ?? []) as $item) {
                    [$t, $d] = self::completeness($f['fields'], (array) $item, $locale);
                    $total += $t;
                    $done += $d;
                }
                continue;
            }
            if (empty($f['tr'])) {
                continue;
            }
            $v = $values[$f['name']] ?? null;
            if (! is_array($v) || empty($v['en'])) {
                continue; // nothing to translate
            }
            $total++;
            if (! empty($v[$locale])) {
                $done++;
            }
        }

        return [$total, $done];
    }
}
