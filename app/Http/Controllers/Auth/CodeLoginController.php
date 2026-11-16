<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Backup sign-in with email + a code from an authenticator app, or a recovery code.
 * Works without email delivery, e.g. when the passkey device is lost.
 */
class CodeLoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login-code');
    }

    public function store(Request $request, Totp $totp): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        $user = User::query()->where('email', mb_strtolower(trim($data['email'])))->first();

        $valid = $user !== null && (
            $totp->verifyForUser($user, $data['code'])
            || $totp->consumeRecoveryCode($user, $data['code'])
        );

        if (! $valid) {
            // One message for unknown address, no app set up and wrong code.
            throw ValidationException::withMessages([
                'code' => __('The email address or the code is not correct.'),
            ]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->passwordConfirmed();

        return redirect()->intended('/');
    }
}
