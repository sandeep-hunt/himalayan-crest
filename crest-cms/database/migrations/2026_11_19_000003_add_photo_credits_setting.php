<?php

use App\Support\Settings;
use App\Support\SiteContent;
use Illuminate\Database\Migrations\Migration;

/**
 * The photo credits list (footer → "Photo credits" pop-up) becomes editable:
 * Admin → Homepage Sections → Photo credits (pop-up). Filled with the website's current list.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Settings::get('photo_credits') === null) {
            Settings::set('photo_credits', SiteContent::defaultPhotoCredits());
        }
    }

    public function down(): void
    {
        // keep the setting
    }
};
