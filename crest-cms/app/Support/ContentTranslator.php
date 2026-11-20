<?php

namespace App\Support;

use App\Models\Section;
use App\Models\UiString;

/**
 * Fills one language with Google translations of ALL English website content:
 * homepage sections, settings, every content list (treks, menu, team, ...) and the
 * buttons & labels. Used when a language is added (or "Translate everything again").
 *
 * The work is split into steps so the admin can show a progress bar:
 *   steps() -> ['ui:Navigation', ..., 'sections', 'settings', 'res:treks', ...]
 *   run($step, $code) translates and saves one step.
 * The chat assistant texts (chat.*) are not translated: the assistant is English only.
 */
class ContentTranslator
{
    public static function steps(): array
    {
        $steps = [];
        foreach (config('cms_ui') as $group => $strings) {
            if (array_filter(array_keys($strings), fn ($k) => ! self::englishOnly($k))) {
                $steps[] = 'ui:'.$group;
            }
        }
        $steps[] = 'sections';
        $steps[] = 'settings';
        foreach (config('cms.resources') as $key => $def) {
            if (collect($def['fields'])->contains(fn ($f) => ! empty($f['tr']))) {
                $steps[] = 'res:'.$key;
            }
        }

        return $steps;
    }

    /** Readable name of a step for the progress popup. */
    public static function label(string $step): string
    {
        return match (true) {
            str_starts_with($step, 'ui:') => 'Buttons & labels: '.substr($step, 3),
            $step === 'sections' => 'Homepage sections',
            $step === 'settings' => 'Settings (contact, footer, SEO…)',
            str_starts_with($step, 'res:') => config('cms.resources.'.substr($step, 4).'.label', $step),
            default => $step,
        };
    }

    /** Keys that stay English on every language (the chat assistant). */
    public static function englishOnly(string $uiKey): bool
    {
        return str_starts_with($uiKey, 'chat.') || $uiKey === 'trek.currency'; // currency code (USD) is never translated
    }

    /** Translate and save one step for language $code. Throws RuntimeException on failure. */
    public static function run(string $step, string $code): void
    {
        if (str_starts_with($step, 'ui:')) {
            self::uiGroup(substr($step, 3), $code);
        } elseif ($step === 'sections') {
            self::sections($code);
        } elseif ($step === 'settings') {
            self::settings($code);
        } elseif (str_starts_with($step, 'res:')) {
            self::resource(substr($step, 4), $code);
        } else {
            abort(404);
        }
        ContentCache::bump();
    }

    /** Remove every stored translation of language $code (when the language is deleted). */
    public static function forget(string $code): void
    {
        abort_if($code === 'en', 422);
        $strip = function (mixed $v) use (&$strip, $code): mixed {
            if (! is_array($v)) {
                return $v;
            }
            if (! array_is_list($v) && array_key_exists('en', $v)) {
                unset($v[$code]);

                return $v;
            }

            return array_map($strip, $v);
        };
        foreach (UiString::query()->get() as $row) {
            $value = (array) $row->value;
            if (array_key_exists($code, $value)) {
                unset($value[$code]);
                $row->value = $value;
                $row->save();
            }
        }
        foreach (Section::query()->get() as $section) {
            $new = $strip((array) $section->content);
            if ($new !== (array) $section->content) {
                $section->content = $new;
                $section->save();
            }
        }
        foreach (Settings::all() as $name => $value) {
            if (is_array($value) && $strip($value) !== $value) {
                Settings::set($name, $strip($value));
            }
        }
        foreach (config('cms.resources') as $def) {
            $trFields = array_column(array_filter($def['fields'], fn ($f) => ! empty($f['tr'])), 'name');
            if (! $trFields) {
                continue;
            }
            foreach ($def['model']::query()->get() as $item) {
                $dirty = false;
                foreach ($trFields as $name) {
                    $v = $item->getAttribute($name);
                    if (is_array($v) && array_key_exists($code, $v)) {
                        unset($v[$code]);
                        $item->setAttribute($name, $v);
                        $dirty = true;
                    }
                }
                if ($dirty) {
                    $item->save();
                }
            }
        }
        \Illuminate\Support\Facades\DB::table('translation_memory')->where('target', $code)->delete();
        ContentCache::bump();
    }

    private static function uiGroup(string $group, string $code): void
    {
        $strings = config("cms_ui.$group");
        abort_unless(is_array($strings), 404);
        $rows = UiString::query()->whereIn('key', array_keys($strings))->get()->keyBy('key');
        $english = [];
        foreach ($strings as $key => $default) {
            if (! self::englishOnly($key)) {
                $english[$key] = ($rows[$key]->value['en'] ?? '') ?: $default;
            }
        }
        foreach (Translator::translate($english, $code) as $key => $text) {
            $row = $rows[$key] ?? new UiString(['key' => $key]);
            $value = (array) ($row->value ?? []);
            $value[$code] = $text;
            $row->value = $value;
            $row->save();
        }
    }

    private static function sections(string $code): void
    {
        foreach (config('cms.sections') as $key => $def) {
            if (empty($def['fields'])) {
                continue;
            }
            $section = Section::where('key', $key)->first();
            if (! $section) {
                continue;
            }
            $content = (array) ($section->content ?? []);
            if (self::translateFields($def['fields'], $content, $code)) {
                $section->content = $content;
                $section->save();
            }
        }
    }

    private static function settings(string $code): void
    {
        $fields = [];
        foreach (config('cms.settings') as $group) {
            foreach ($group['fields'] as $f) {
                if (! empty($f['tr'])) {
                    $fields[] = $f;
                }
            }
        }
        $values = Settings::all();
        $data = array_intersect_key($values, array_flip(array_column($fields, 'name')));
        if (self::translateFields($fields, $data, $code)) {
            foreach ($data as $name => $value) {
                if (($values[$name] ?? null) !== $value) {
                    Settings::set($name, $value);
                }
            }
        }
    }

    private static function resource(string $key, string $code): void
    {
        $def = config("cms.resources.$key");
        abort_unless($def, 404);
        $trFields = array_values(array_filter($def['fields'], fn ($f) => ! empty($f['tr'])));
        foreach ($def['model']::query()->get() as $item) {
            $data = [];
            foreach ($trFields as $f) {
                $data[$f['name']] = $item->getAttribute($f['name']);
            }
            if (self::translateFields($trFields, $data, $code)) {
                foreach ($data as $name => $value) {
                    $item->setAttribute($name, $value);
                }
                $item->save();
            }
        }
    }

    /**
     * Translate the English value of every translatable field in $data (nested groups too)
     * into $code, in one batch. Returns true when something was written.
     */
    public static function translateFields(array $fields, array &$data, string $code): bool
    {
        $units = [];
        self::collect($fields, $data, [], $units);
        if (! $units) {
            return false;
        }
        $done = Translator::translate(array_map(fn ($u) => $u['text'], $units), $code);
        foreach ($units as $i => $u) {
            $value = $u['lines'] ? Fields::splitLines($done[$i] ?? '') : ($done[$i] ?? '');
            $ref = &$data;
            foreach ($u['path'] as $p) {
                if (! is_array($ref)) {
                    $ref = [];
                }
                $ref = &$ref[$p];
            }
            // a plain (non-locale) string becomes {"en": ...}
            if (! is_array($ref) || array_is_list($ref)) {
                $ref = ['en' => $ref];
            }
            if ($value === '' || $value === []) {
                unset($ref[$code]);
            } else {
                $ref[$code] = $value;
            }
            unset($ref);
        }

        return true;
    }

    private static function collect(array $fields, array $data, array $path, array &$units): void
    {
        foreach ($fields as $f) {
            $name = $f['name'];
            if ($f['type'] === 'group') {
                foreach ((array) ($data[$name] ?? []) as $i => $item) {
                    self::collect($f['fields'], (array) $item, [...$path, $name, $i], $units);
                }

                continue;
            }
            if (empty($f['tr'])) {
                continue;
            }
            $v = $data[$name] ?? null;
            $en = is_array($v) && ! array_is_list($v) ? ($v['en'] ?? null) : $v;
            $lines = $f['type'] === 'lines';
            $text = is_array($en) ? implode("\n", $en) : (string) $en;
            if (trim($text) !== '') {
                $units[] = ['path' => [...$path, $name], 'text' => $text, 'lines' => $lines];
            }
        }
    }
}
