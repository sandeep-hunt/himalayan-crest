<?php

namespace App\Models;

use App\Support\ContentCache;
use Illuminate\Database\Eloquent\Model;

class UiString extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => ContentCache::bump());
    }

    /** All known keys => English default (from config/cms_ui.php). */
    public static function defaults(): array
    {
        $known = [];
        foreach (config('cms_ui') as $strings) {
            $known += $strings;
        }

        return $known;
    }

    /**
     * Save texts in every language: [key => [locale => text]]. English follows saveTexts' rules
     * (empty / equal to the default = not stored); other languages are stored when not empty.
     * Chat assistant keys keep English only.
     */
    public static function saveAll(array $strings): void
    {
        $byLocale = [];
        foreach ($strings as $key => $perLocale) {
            foreach ((array) $perLocale as $locale => $text) {
                if (! array_key_exists($locale, config('cms.locales')) || ($locale !== 'en' && \App\Support\ContentTranslator::englishOnly((string) $key))) {
                    continue;
                }
                $byLocale[$locale][$key] = is_string($text) ? $text : '';
            }
        }
        foreach ($byLocale as $locale => $texts) {
            static::saveTexts($texts, (string) $locale);
        }
    }

    /**
     * Save edited texts for one language. Unknown keys are ignored; an empty text, or English
     * equal to the built-in default, is not stored (so the default shows).
     */
    public static function saveTexts(array $strings, string $locale): void
    {
        $known = static::defaults();
        $existing = static::query()->get()->keyBy('key');

        foreach ($strings as $key => $text) {
            if (! array_key_exists($key, $known)) {
                continue;
            }
            $text = trim((string) $text);
            $row = $existing[$key] ?? new static(['key' => $key]);
            $before = (array) ($row->value ?? []);
            $value = $before;
            if ($text === '' || ($locale === 'en' && $text === $known[$key])) {
                unset($value[$locale]);
            } else {
                $value[$locale] = $text;
            }
            if ($value !== $before && ($row->exists || $value)) {
                $row->value = $value;
                $row->save();
            }
        }
    }
}
