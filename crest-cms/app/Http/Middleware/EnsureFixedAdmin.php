<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Admin\AuthController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Only the one fixed admin account may use the admin panel; any other session is signed out. */
class EnsureFixedAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && strtolower($user->email) !== AuthController::adminEmail()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Please sign in with the admin account.']);
        }

        return $next($request);
    }
}
