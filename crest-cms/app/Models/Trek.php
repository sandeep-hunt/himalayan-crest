<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Trek extends CmsModel
{
    public array $translatable = [
        'name', 'badge', 'summary', 'difficulty', 'grade', 'area', 'region', 'start_point', 'season',
        'highlights', 'itinerary', 'inclusions', 'exclusions',
    ];

    public array $jsonFields = ['months', 'tags', 'gallery'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'days' => 'integer', 'price' => 'integer', 'max_altitude' => 'integer'];
    }

    public function departures(): HasMany
    {
        return $this->hasMany(Departure::class)->orderBy('date');
    }

    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    {
        return $q->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('id');
    }

    /** "5,545 m" */
    public function altitudeLabel(): string
    {
        return number_format($this->max_altitude).' m';
    }
}
