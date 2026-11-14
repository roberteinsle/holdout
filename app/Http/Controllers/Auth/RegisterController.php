<?php

namespace App\Http\Controllers\Auth;

use App\Actions\SendMagicLink;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * v0.1 runs one household per installation: only the very first person can
 * register and becomes its owner. Everyone else joins by invitation.
 */
class RegisterController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Household::query()->exists()) {
            return redirect()->route('login');
        }

        return view('auth.register');
    }

    public function store(Request $request, SendMagicLink $sendMagicLink): RedirectResponse
    {
        $data = $request->validate([
            'household' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $user = Cache::lock('holdout:register', 10)->block(5, function () use ($data, $request) {
            return DB::transaction(function () use ($data, $request) {
                if (Household::query()->exists()) {
                    return null;
                }

                $household = Household::query()->create([
                    'name' => $data['household'],
                    'locale' => $request->getPreferredLanguage(['de', 'en']) ?? 'de',
                ]);

                $user = new User([
                    'name' => $data['name'],
                    'email' => mb_strtolower(trim($data['email'])),
                ]);
                $user->household()->associate($household);
                $user->role = Role::Owner;
                $user->save();

                return $user;
            });
        });

        if ($user === null) {
            return redirect()->route('login');
        }

        $sendMagicLink->forUser($user);

        return redirect()->route('login.sent');
    }
}
