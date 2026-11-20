<?php

namespace App\Models;

class Post extends CmsModel
{

    public array $translatable = ['title', 'category', 'excerpt', 'content'];

    public array $jsonFields = [];

    protected function casts(): array
    {
        return ['published_at' => 'date:Y-m-d'];
    }

    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    {
        return $q->orderByDesc('published_at')->orderByDesc('id');
    }
}
