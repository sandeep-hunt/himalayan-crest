<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/** "Your account": change the display name and the password of the one fixed admin login. */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validateWithBag('profile', ['name' => 'required|string|max:100']);
        $request->user()->forceFill(['name' => $data['name']])->save();

        return back()->with('success', 'Your name was updated.');
    }

    public function password(Request $request)
    {
        $user = $request->user();
        $data = $request->validateWithBag('password', [
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(10)->letters()->numbers()],
        ], ['password.different' => 'The new password must be different from the current one.']);

        $user->password = $data['password'];
        $user->save();

        // sign out any other device that used the old password
        $request->session()->regenerate();
        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();

        return back()->with('success', 'Your password was changed. Use the new password next time you sign in.');
    }
}
