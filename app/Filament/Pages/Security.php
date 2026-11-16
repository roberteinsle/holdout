<?php

namespace App\Filament\Pages;

use App\Support\Totp;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Passkey;
use Livewire\Attributes\Url;

/**
 * Each user's sign-in methods: passkeys and the authenticator-app backup.
 */
class Security extends Page
{
    protected string $view = 'filament.pages.security';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 90;

    #[Url]
    public bool $welcome = false;

    /** Shown during authenticator setup; the secret itself stays in the session. */
    public ?string $totpQrCode = null;

    public ?string $totpSecretForDisplay = null;

    public string $totpCode = '';

    /** @var list<string> Shown once after setup or regeneration. */
    public array $recoveryCodes = [];

    public static function getNavigationLabel(): string
    {
        return __('Sign-in & security');
    }

    public function getTitle(): string
    {
        return __('Sign-in & security');
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

        if (! $this->ensureRecentlyConfirmed('deletePasskey', [$id])) {
            return;
        }

        $deletePasskey($user, $passkey);

        Notification::make()->title(__('Passkey removed.'))->success()->send();
    }

    public function startTotpSetup(Totp $totp): void
    {
        if (! $this->ensureRecentlyConfirmed('startTotpSetup')) {
            return;
        }

        $secret = $totp->generateSecret();
        session()->put('totp.pending_secret', $secret);

        $this->totpQrCode = $totp->qrCode(auth()->user(), $secret);
        $this->totpSecretForDisplay = trim(chunk_split($secret, 4, ' '));
        $this->totpCode = '';
    }

    public function confirmTotpSetup(Totp $totp): void
    {
        $this->validate(['totpCode' => ['required', 'string', 'max:10']]);

        $secret = session('totp.pending_secret');

        if (! is_string($secret) || ! $totp->verifySecret($secret, $this->totpCode)) {
            $this->addError('totpCode', __('This code is not correct. Check the time on your phone and try again.'));

            return;
        }

        $this->recoveryCodes = $totp->generateRecoveryCodes();

        auth()->user()->forceFill([
            'totp_secret' => $secret,
            'totp_confirmed_at' => now(),
            'totp_recovery_codes' => $this->recoveryCodes,
            'totp_last_used_step' => null,
        ])->save();

        session()->forget('totp.pending_secret');
        $this->reset('totpQrCode', 'totpSecretForDisplay', 'totpCode');

        Notification::make()->title(__('Authenticator app set up.'))->success()->send();
    }

    public function cancelTotpSetup(): void
    {
        session()->forget('totp.pending_secret');
        $this->reset('totpQrCode', 'totpSecretForDisplay', 'totpCode');
    }

    public function regenerateRecoveryCodes(Totp $totp): void
    {
        $user = auth()->user();

        if (! $user->hasTotpEnabled() || ! $this->ensureRecentlyConfirmed('regenerateRecoveryCodes')) {
            return;
        }

        $this->recoveryCodes = $totp->generateRecoveryCodes();
        $user->forceFill(['totp_recovery_codes' => $this->recoveryCodes])->save();
    }

    public function disableTotp(): void
    {
        if (! $this->ensureRecentlyConfirmed('disableTotp')) {
            return;
        }

        auth()->user()->forceFill([
            'totp_secret' => null,
            'totp_confirmed_at' => null,
            'totp_recovery_codes' => null,
            'totp_last_used_step' => null,
        ])->save();

        $this->recoveryCodes = [];

        Notification::make()->title(__('Authenticator app removed.'))->success()->send();
    }

    /**
     * Changing sign-in methods needs a recent sign-in, the same rule
     * laravel/passkeys applies to adding passkeys. If it is too old, users with a
     * passkey confirm with it in the browser and the action runs again;
     * everyone else is asked to sign in again with an email link.
     *
     * @param  array<int, mixed>  $arguments
     */
    private function ensureRecentlyConfirmed(string $retryMethod, array $arguments = []): bool
    {
        $confirmedAt = (int) session('auth.password_confirmed_at', 0);

        if (time() - $confirmedAt <= (int) config('auth.password_timeout', 10800)) {
            return true;
        }

        if (auth()->user()->hasPasskeysEnabled()) {
            $this->dispatch('holdout-confirm-passkey', method: $retryMethod, arguments: $arguments);

            return false;
        }

        Notification::make()
            ->title(__('For your security, please sign in again before changing how you sign in.'))
            ->body(__('Sign out and sign in again with an email link, then try once more.'))
            ->warning()
            ->persistent()
            ->send();

        return false;
    }
}
