<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Image uploads are stored directly under public/uploads (no storage symlink needed,
 * which keeps deployment simple on shared hosting such as Hostinger).
 * Database values are relative paths like "uploads/treks/2026/11/abc.jpg".
 */
class Media
{
    public const RULE = 'image|mimes:jpg,jpeg,png,webp,gif|max:6144';

    public static function store(UploadedFile $file, string $folder): string
    {
        $dir = 'uploads/'.trim($folder, '/').'/'.date('Y/m');
        File::ensureDirectoryExists(public_path($dir));
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'image';
        $name = Str::limit($base, 40, '').'-'.Str::lower(Str::random(6)).'.'.$ext;
        $file->move(public_path($dir), $name);

        return $dir.'/'.$name;
    }

    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }

    /** Delete a file we uploaded (never touches seeded images or anything outside uploads/). */
    public static function delete(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'uploads/') || str_starts_with($path, 'uploads/seed/') || str_contains($path, '..')) {
            return;
        }
        File::delete(public_path($path));
    }
}
