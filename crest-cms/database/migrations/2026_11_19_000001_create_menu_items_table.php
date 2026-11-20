<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Website navigation (header menus and footer link columns), managed in Admin -> Menu.
 *   Top-level row (parent_id NULL): a header menu ("Treks", "About"...) or a footer column ("Explore").
 *     location = header | footer, style = list | mega | departures | guides | peaks
 *   Child row (parent_id set): one link inside that menu.
 *     column = heading it is grouped under (mega menus), action + target = what the link does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $t->string('location', 10)->default('header');
            $t->string('style', 20)->nullable();
            $t->json('title');
            $t->json('subtitle')->nullable();
            $t->json('column')->nullable();
            $t->string('action', 20)->nullable();
            $t->string('target', 300)->nullable();
            $t->boolean('is_button')->default(false);
            $t->integer('sort_order')->default(0);
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
