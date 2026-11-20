<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // First admin account. Change the password after the first login (Admin → your name).
        $email = env('ADMIN_EMAIL', 'admin@cresthimalaya.com');
        if (! User::where('email', $email)->exists()) {
            User::create([
                'name' => env('ADMIN_NAME', 'Crest Himalaya Admin'),
                'email' => $email,
                'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe@2026')),
                'is_active' => true,
                'approved_at' => now(),
            ]);
            $this->command?->warn("Admin user created: $email (password from ADMIN_PASSWORD, default ChangeMe@2026). Change it after logging in.");
        }

        $this->call(ContentSeeder::class);
    }
}
