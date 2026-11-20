<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Departure extends CmsModel
{
    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'seats' => 'integer'];
    }

    public function trek(): BelongsTo
    {
        return $this->belongsTo(Trek::class);
    }

    public function getTrekNameAttribute(): string
    {
        return (string) ($this->trek?->tr('name', 'en') ?? '—');
    }

    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    {
        return $q->orderBy('date');
    }
}
