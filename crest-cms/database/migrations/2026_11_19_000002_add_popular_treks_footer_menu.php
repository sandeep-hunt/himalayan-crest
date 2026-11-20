<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

/**
 * The footer "Popular treks" column becomes an editable footer menu (Admin -> Menu).
 * Only added to databases that already have menus (a fresh install gets it from the seeder).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (MenuItem::count() === 0) {
            return;
        }
        $exists = MenuItem::whereNull('parent_id')->where('location', 'footer')->get()
            ->contains(fn ($m) => strcasecmp((string) $m->tr('title', 'en'), 'Popular treks') === 0);
        if ($exists) {
            return;
        }
        $order = (int) MenuItem::whereNull('parent_id')->where('location', 'footer')->max('sort_order') + 1;
        $menu = MenuItem::create(['title' => ['en' => 'Popular treks'], 'location' => 'footer', 'style' => 'list', 'sort_order' => $order]);
        $links = [['Everest Base Camp Trek', 'ebc'], ['Annapurna Circuit Trek', 'acircuit'], ['Annapurna Base Camp', 'abc'], ['Gokyo Lakes & Renjo La', 'gokyo'], ['Mera Peak Climb', 'mera']];
        foreach ($links as $i => [$title, $slug]) {
            MenuItem::create(['parent_id' => $menu->id, 'title' => ['en' => $title], 'action' => 'trek', 'target' => $slug, 'sort_order' => $i + 1]);
        }
    }

    public function down(): void
    {
        MenuItem::whereNull('parent_id')->where('location', 'footer')->get()
            ->filter(fn ($m) => $m->tr('title', 'en') === 'Popular treks')->each->delete();
    }
};
