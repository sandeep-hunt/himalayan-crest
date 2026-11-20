<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Remembers every Google translation (English text + language), so unchanged text is never translated twice. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_memory', function (Blueprint $table) {
            $table->id();
            $table->char('hash', 40)->unique(); // sha1(language + English text)
            $table->string('target', 10)->index();
            $table->longText('translated');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_memory');
    }
};
