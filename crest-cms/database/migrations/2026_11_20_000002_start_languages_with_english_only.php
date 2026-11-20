<?php

use App\Support\Settings;
use Illuminate\Database\Migrations\Migration;

/**
 * Multi-language goes live: the client adds the languages they want in Admin -> Languages,
 * and each new language is filled automatically with Google Translate. Start the list with
 * English only (the 15 built-in sample languages were never switched on).
 */
return new class extends Migration
{
    public function up(): void
    {
        $list = (array) Settings::get('languages', []);
        $shown = array_filter($list, fn ($l) => ($l['code'] ?? '') !== 'en' && ! empty($l['enabled']));
        if ($shown) {
            return; // languages already in use: keep them
        }
        $en = collect($list)->firstWhere('code', 'en') ?: ['code' => 'en', 'name' => 'English', 'native' => 'English', 'rtl' => false];
        Settings::set('languages', [array_merge($en, ['enabled' => true, 'translated' => true])]);
    }

    public function down(): void {}
};
