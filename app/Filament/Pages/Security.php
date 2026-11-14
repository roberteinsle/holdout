<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Passkey;
use Livewire\Attributes\Url;

/**
 * Lets each user add and remove their passkeys.
 */
class Security extends Page
{
    protected string $view = 'filament.pages.security';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 90;

    #[Url]
    public bool $welcome = false;

    public static function getNavigationLabel(): string
    {
        return __('Passkeys');
    }

    public function getTitle(): string
    {
        return __('Passkeys');
    }

    /** @return Collection<int, Passkey> */
    public function getPasskeys(): Collection
    {
        return auth()->user()->passkeys()->latest()->get();
    }

    public function deletePasskey(int $id, DeletePasskey $deletePasskey): void
    {
        $user = auth()->user();
        $passkey = $user->passkeys()->findOrFail($id);

        // Same rule as adding one: only shortly after signing in.
        $confirmedAt = (int) session('auth.password_confirmed_at', 0);

        if (time() - $confirmedAt > (int) config('auth.password_timeout', 10800)) {
            Notification::make()
                ->title(__('For your security, please sign in again before removing a passkey.'))
                ->warning()
                ->send();

            return;
        }

        $deletePasskey($user, $passkey);

        Notification::make()->title(__('Passkey removed.'))->success()->send();
    }
}
