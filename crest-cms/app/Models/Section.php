<?php

namespace App\Models;

use App\Support\ContentCache;
use Illuminate\Database\Eloquent\Model;

/** A homepage section's editable copy. content = { field: value | {"en": ...} | [group items] } */
class Section extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => ContentCache::bump());
    }
}
