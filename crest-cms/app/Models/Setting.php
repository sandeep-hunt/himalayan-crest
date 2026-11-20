<?php

namespace App\Models;

use App\Support\ContentCache;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
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
}
