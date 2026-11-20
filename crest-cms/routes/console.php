<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * The admin panel has ONE fixed login: ADMIN_EMAIL / ADMIN_PASSWORD in .env.
 * After changing them in .env, run:   php artisan admin:sync
 */
Artisan::command('admin:sync', function () {
    $email = strtolower((string) env('ADMIN_EMAIL', 'admin@cresthimalaya.com'));
    $password = (string) env('ADMIN_PASSWORD', '');
    if (strlen($password) < 8) {
        $this->error('Set ADMIN_PASSWORD in .env (at least 8 characters) first.');

        return 1;
    }

    // keep exactly one account: the fixed admin
    $admin = User::where('email', $email)->first() ?? User::orderBy('id')->first() ?? new User(['name' => 'Crest Himalaya Admin']);
    $admin->forceFill(['email' => $email, 'password' => $password, 'is_active' => true, 'approved_at' => $admin->approved_at ?? now()])->save();
    $removed = User::whereKeyNot($admin->getKey())->delete();
    DB::table('sessions')->delete(); // sign everyone out so the new password applies

    $this->info("Admin login is now: {$email} (password from ADMIN_PASSWORD).".($removed ? " Removed {$removed} other account(s)." : ''));

    return 0;
})->purpose('Set the fixed admin login from ADMIN_EMAIL / ADMIN_PASSWORD in .env');
