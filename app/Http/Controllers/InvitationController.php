<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function show(string $token): View
    {
        return view('invitations.show', [
            'token' => $token,
            'invitation' => Invitation::findValid($token)?->load('household', 'inviter'),
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $user = DB::transaction(function () use ($token, $data, $request) {
            $invitation = Invitation::findValid($token);

            if ($invitation === null || User::query()->where('email', $invitation->email)->exists()) {
                return null;
            }

            $user = new User(['name' => $data['name'], 'email' => $invitation->email, 'locale' => Locales::fromBrowser($request)]);
            $user->household_id = $invitation->household_id;
            $user->role = $invitation->role;
            // The invite link arrived by mail, so the address is confirmed.
            $user->email_verified_at = now();
            $user->save();

            $invitation->delete();

            return $user;
        });

        if ($user === null) {
            return redirect()->route('invitations.show', $token);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        // A fresh email-link sign-in counts as confirmation for adding passkeys.
        $request->session()->passwordConfirmed();

        return redirect()->route('filament.app.pages.profile', ['welcome' => 1]);
    }
}
