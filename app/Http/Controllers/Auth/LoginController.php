<?php

namespace App\Http\Controllers\Auth;

use App\Actions\SendMagicLink;
use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\LoginToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (! Household::query()->exists()) {
            return redirect()->route('register');
        }

        return view('auth.login');
    }

    public function sendLink(Request $request, SendMagicLink $sendMagicLink): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $sendMagicLink($data['email']);

        // Same answer whether or not the address exists.
        return redirect()->route('login.sent');
    }

    public function sent(): View
    {
        return view('auth.link-sent', ['minutes' => LoginToken::LIFETIME_MINUTES]);
    }

    /**
     * Opening the link only shows a button. Mail scanners that prefetch links
     * therefore cannot use up the single-use token.
     */
    public function confirm(string $token): View
    {
        return view('auth.confirm-login', [
            'token' => $token,
            'valid' => LoginToken::findValid($token) !== null,
        ]);
    }

    public function consume(Request $request, string $token): RedirectResponse
    {
        $user = DB::transaction(function () use ($token) {
            $loginToken = LoginToken::findValid($token);

            if ($loginToken === null) {
                return null;
            }

            // Delete first: the link works exactly once.
            $loginToken->delete();

            return $loginToken->user;
        });

        if ($user === null) {
            return redirect()->route('login.confirm', $token);
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        // A fresh email-link sign-in counts as confirmation for adding passkeys.
        $request->session()->passwordConfirmed();

        if (! $user->hasPasskeysEnabled()) {
            return redirect()->route('filament.app.pages.security', ['welcome' => 1]);
        }

        return redirect()->intended('/');
    }
}
