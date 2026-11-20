<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sign in to the admin panel. There is ONE fixed admin account
 * (ADMIN_EMAIL / ADMIN_PASSWORD in .env, created by `php artisan db:seed`).
 * No registration, no password reset, no other users.
 */
class AuthController extends Controller
{
    public static function adminEmail(): string
    {
        return strtolower((string) env('ADMIN_EMAIL', 'admin@cresthimalaya.com'));
    }

    public function show()
    {
        return Auth::check() ? redirect()->route('admin.dashboard') : view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $email = strtolower(trim($credentials['email']));

        $key = 'admin-login:'.$email.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        // Only the fixed admin account can sign in
        if ($email !== self::adminEmail() || ! Auth::attempt(['email' => $email, 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, 300);
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        Auth::user()->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }
}
