<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Crest Himalaya CMS tables.
 * Translatable text columns are JSON objects keyed by locale: {"en": "...", "es": "..."}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treks', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 60)->unique();
            $t->json('name');
            $t->string('category', 20)->default('popular')->index();
            $t->json('badge')->nullable();
            $t->json('summary')->nullable();
            $t->unsignedSmallInteger('days')->default(1);
            $t->json('difficulty')->nullable();
            $t->json('grade')->nullable();
            $t->unsignedSmallInteger('max_altitude')->default(0);
            $t->unsignedInteger('price')->default(0);
            $t->json('area')->nullable();
            $t->json('region')->nullable();
            $t->json('start_point')->nullable();
            $t->json('season')->nullable();
            $t->json('months')->nullable();
            $t->json('tags')->nullable();
            $t->string('image')->nullable();
            $t->json('gallery')->nullable();
            $t->json('highlights')->nullable();
            $t->json('itinerary')->nullable();
            $t->json('inclusions')->nullable();
            $t->json('exclusions')->nullable();
            $t->boolean('is_featured')->default(false);
            $t->integer('sort_order')->default(0);
            $t->boolean('is_published')->default(true)->index();
            $t->timestamps();
        });

        Schema::create('departures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('trek_id')->constrained()->cascadeOnDelete();
            $t->date('date')->index();
            $t->unsignedTinyInteger('seats')->default(0);
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->json('title');
            $t->json('description')->nullable();
            $t->json('link_label')->nullable();
            $t->string('action', 20)->default('trek');
            $t->string('icon', 20)->nullable();
            $t->string('image')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });

        Schema::create('team_members', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->json('designation')->nullable();
            $t->json('experience')->nullable();
            $t->json('regions')->nullable();
            $t->json('bio')->nullable();
            $t->string('photo')->nullable();
            $t->string('background')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });

        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->string('traveller_name');
            $t->json('country')->nullable();
            $t->json('trek_label')->nullable();
            $t->json('text');
            $t->unsignedTinyInteger('rating')->nullable();
            $t->string('photo')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });

        Schema::create('guides', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 80)->unique();
            $t->json('title');
            $t->json('summary')->nullable();
            $t->json('content')->nullable();
            $t->string('icon', 20)->nullable();
            $t->string('image')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });

        Schema::create('posts', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 120)->unique();
            $t->json('title');
            $t->json('category')->nullable();
            $t->date('published_at')->nullable()->index();
            $t->json('excerpt')->nullable();
            $t->json('content')->nullable();
            $t->string('image')->nullable();
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });

        Schema::create('faqs', function (Blueprint $t) {
            $t->id();
            $t->json('question');
            $t->json('answer');
            $t->integer('sort_order')->default(0);
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });

        Schema::create('videos', function (Blueprint $t) {
            $t->id();
            $t->json('title');
            $t->string('type', 10)->default('video');
            $t->string('url', 500);
            $t->string('image')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });

        // Homepage section copy: one row per section key, content is a JSON document.
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->string('key', 40)->unique();
            $t->json('content')->nullable();
            $t->timestamps();
        });

        // Key/value website settings. value is JSON (string, list or {"en": ...}).
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key', 60)->unique();
            $t->json('value')->nullable();
            $t->timestamps();
        });

        // Interface text translations: {"es": "...", "fr": "..."}; defaults live in config/cms_ui.php
        Schema::create('ui_strings', function (Blueprint $t) {
            $t->id();
            $t->string('key', 80)->unique();
            $t->json('value')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['ui_strings', 'settings', 'sections', 'videos', 'faqs', 'posts', 'guides', 'reviews', 'team_members', 'categories', 'departures', 'treks'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
