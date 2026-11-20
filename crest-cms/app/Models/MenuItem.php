<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A header menu / footer column (top level) or one link inside it. */
class MenuItem extends CmsModel
{
    public array $translatable = ['title', 'subtitle', 'column'];

    public const ACTIONS = [
        'section' => 'Scroll to a homepage section',
        'filter' => 'Show treks (filter the trek list)',
        'trek' => 'Open a trek',
        'plan' => 'Open the Plan Your Trip form',
        'chat' => 'Open the chat assistant on a topic',
        'guides' => 'Open the list of travel guides',
        'articles' => 'Open the list of articles',
        'url' => 'Open a web address',
    ];

    protected function casts(): array
    {
        return ['is_button' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        parent::booted();
        // Keep menu rows and link rows consistent whatever was filled in the form
        static::saving(function (MenuItem $m) {
            if ($m->parent_id) {
                $m->location = $m->parent?->location ?? 'header';
                $m->style = null;
                $m->action ??= 'section';
            } else {
                $m->location = in_array($m->location, ['header', 'footer'], true) ? $m->location : 'header';
                $m->style = $m->style ?: 'list';
                $m->action = $m->target = null;
                $m->column = $m->subtitle = null;
                $m->is_button = false;
            }
        });
        // a menu moved to the other location takes its links with it
        static::saved(function (MenuItem $m) {
            if (! $m->parent_id && $m->wasChanged('location')) {
                static::where('parent_id', $m->id)->update(['location' => $m->location]);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /** Menus first (header, then footer), each followed by its links. */
    public function scopeOrdered(Builder $q): Builder
    {
        $parent = fn (string $col) => "COALESCE((SELECT p.$col FROM menu_items p WHERE p.id = menu_items.parent_id), menu_items.$col)";

        return $q->orderByRaw("CASE {$parent('location')} WHEN 'header' THEN 0 ELSE 1 END")
            ->orderByRaw($parent('sort_order'))
            ->orderByRaw('COALESCE(menu_items.parent_id, menu_items.id)')
            ->orderByRaw('CASE WHEN menu_items.parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('menu_items.sort_order')
            ->orderBy('menu_items.id');
    }

    // ---- list-view helpers ----
    public function getPlaceAttribute(): string
    {
        if (! $this->parent_id) {
            return ($this->location === 'footer' ? 'Footer column' : 'Header menu');
        }

        return '↳ in '.($this->parent?->tr('title', 'en') ?? '?');
    }

    public function getOpensAttribute(): string
    {
        if (! $this->parent_id) {
            return [
                'list' => 'Dropdown list', 'mega' => 'Large menu with columns', 'departures' => 'Upcoming departures (automatic)',
                'guides' => 'Travel guides (automatic) + links', 'peaks' => 'Peak climbs (automatic) + links',
            ][$this->style ?? 'list'] ?? 'Dropdown list';
        }

        return (self::ACTIONS[$this->action] ?? '—').($this->target ? ': '.$this->target : '');
    }
}
