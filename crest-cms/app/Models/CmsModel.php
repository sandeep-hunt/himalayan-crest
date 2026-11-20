<?php

namespace App\Models;

use App\Support\ContentCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base model for CMS content.
 * - $translatable fields are JSON objects keyed by locale ({"en": "...", "es": "..."}).
 * - $jsonFields are plain JSON arrays (gallery, months, tags).
 * - Any save/delete clears the public API cache.
 */
abstract class CmsModel extends Model
{
    protected $guarded = ['id'];

    /** @var list<string> */
    public array $translatable = [];

    /** @var list<string> */
    public array $jsonFields = [];

    protected static function booted(): void
    {
        static::saved(fn () => ContentCache::bump());
        static::deleted(fn () => ContentCache::bump());
    }

    public function getCasts(): array
    {
        $casts = parent::getCasts();
        foreach ([...$this->translatable, ...$this->jsonFields] as $field) {
            $casts[$field] = 'array';
        }
        $casts['is_published'] ??= 'boolean';
        return $casts;
    }

    public function isTranslatable(string $field): bool
    {
        return in_array($field, $this->translatable, true);
    }

    /**
     * Value of a translatable field for a locale, falling back to English.
     * Empty strings / empty lists count as "not translated".
     */
    public function tr(string $field, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();
        $value = $this->getAttribute($field);
        if (! $this->isTranslatable($field)) {
            return $value;
        }
        if (! is_array($value)) {
            return $value;
        }
        $fallback = config('cms.fallback_locale', 'en');
        foreach ([$locale, $fallback] as $loc) {
            if (isset($value[$loc]) && $value[$loc] !== '' && $value[$loc] !== []) {
                return $value[$loc];
            }
        }
        return null;
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where($this->getTable().'.is_published', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('id');
    }
}
