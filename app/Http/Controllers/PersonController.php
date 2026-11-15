<?php

namespace App\Http\Controllers;

use App\Enums\MemberType;
use App\Models\Household;
use App\Models\User;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Holdout runs on the home network without sign-in. Each device asks once
 * "Who are you?" and remembers the answer, so the history shows who did what.
 */
class PersonController extends Controller
{
    public function choose(): View|RedirectResponse
    {
        $household = Household::query()->first();

        if ($household === null) {
            return redirect()->route('setup');
        }

        return view('people.choose', [
            'household' => $household,
            'people' => $household->users()->orderBy('name')->get(),
        ]);
    }

    public function select(Request $request, User $user): RedirectResponse
    {
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    /** Add yourself from the "Who are you?" page. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $household = Household::query()->firstOrFail();
        $user = $household->users()->create(['name' => $data['name'], 'locale' => Locales::fromBrowser($request)]);

        return $this->select($request, $user);
    }

    public function setup(): View|RedirectResponse
    {
        if (Household::query()->exists()) {
            return redirect()->route('login');
        }

        return view('people.setup');
    }

    public function storeSetup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'household' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:100'],
            'adults' => ['required', 'integer', 'min:1', 'max:30'],
            'children' => ['required', 'integer', 'min:0', 'max:30'],
        ]);

        $user = DB::transaction(function () use ($data, $request) {
            if (Household::query()->lockForUpdate()->exists()) {
                return null;
            }

            $household = Household::query()->create(['name' => $data['household']]);
            $household->setMemberCount(MemberType::Adult, (int) $data['adults']);
            $household->setMemberCount(MemberType::Child, (int) $data['children']);

            return $household->users()->create(['name' => $data['name'], 'locale' => Locales::fromBrowser($request)]);
        });

        if ($user === null) {
            return redirect()->route('login');
        }

        return $this->select($request, $user);
    }
}
