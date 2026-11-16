<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit="save" class="space-y-4">
            {{ $this->form }}
            <x-filament::button type="submit">{{ __('Save') }}</x-filament::button>
        </form>
    </x-filament::section>

    {{-- Re-confirm with a passkey when the last sign-in is too old, then repeat the action. --}}
    <div
        x-data
        x-on:holdout-confirm-passkey.window="
            window.holdoutPasskeys.confirm()
                .then(() => $wire.call($event.detail.method, ...($event.detail.arguments ?? [])))
                .catch(() => {})
        "
    ></div>

    @if ($welcome && $this->getPasskeys()->isEmpty())
        <x-filament::section>
            <x-slot name="heading">{{ __('Welcome to :app!', ['app' => config('app.name')]) }}</x-slot>
            {{ __('Set up a passkey now, and next time you sign in with your fingerprint, face or device PIN instead of waiting for an email.') }}
        </x-filament::section>
    @endif

    {{-- Own wrapper: x-filament::section already has an x-data, a second one on it is ignored. --}}
    <div
        x-data="{
            name: '',
            busy: false,
            error: null,
            async add() {
                this.error = null;
                this.busy = true;
                try {
                    await window.holdoutPasskeys.register(this.name);
                    this.name = '';
                    $wire.$refresh();
                } catch (e) {
                    this.error = {
                        UserCancelledError: null,
                        PasskeyExistsError: @js(__('This device already has a passkey for your account.')),
                        NotSupportedError: @js(__('This browser does not support passkeys.')),
                        ReauthRequired: @js(__('For your security, please sign in again with a new email link, then add the passkey.')),
                    }[e.name] ?? @js(__('The passkey could not be added. Please try again.'));
                } finally {
                    this.busy = false;
                }
            },
        }"
    >
    <x-filament::section>
        <x-slot name="heading">{{ __('Add a passkey') }}</x-slot>
        <x-slot name="description">{{ __('Give it a name so you can recognise the device later.') }}</x-slot>

        <form x-on:submit.prevent="add" class="flex flex-col gap-3 sm:flex-row">
            <x-filament::input.wrapper class="flex-1">
                <x-filament::input
                    type="text"
                    x-model="name"
                    required
                    maxlength="255"
                    :placeholder="__('e.g. My phone')"
                />
            </x-filament::input.wrapper>
            <x-filament::button type="submit" icon="heroicon-o-finger-print" x-bind:disabled="busy || !name">
                {{ __('Add passkey') }}
            </x-filament::button>
        </form>
        <p x-show="error" x-text="error" x-cloak class="mt-3 text-sm text-danger-600 dark:text-danger-400"></p>
    </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">{{ __('Your passkeys') }}</x-slot>

        @forelse ($this->getPasskeys() as $passkey)
            <div class="flex items-center justify-between gap-3 border-b border-gray-200 py-3 last:border-0 dark:border-white/10" wire:key="passkey-{{ $passkey->id }}">
                <div>
                    <div class="font-medium">{{ $passkey->name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Added :date', ['date' => $passkey->created_at->isoFormat('LL')]) }}
                        @if ($passkey->last_used_at)
                            · {{ __('Last used :date', ['date' => $passkey->last_used_at->diffForHumans()]) }}
                        @endif
                    </div>
                </div>
                <x-filament::button
                    color="danger"
                    outlined
                    size="sm"
                    wire:click="deletePasskey({{ $passkey->id }})"
                    wire:confirm="{{ __('Remove this passkey?') }}"
                >
                    {{ __('Remove') }}
                </x-filament::button>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No passkeys yet. You sign in with an email link until you add one.') }}</p>
        @endforelse
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">{{ __('Authenticator app (backup)') }}</x-slot>
        <x-slot name="description">{{ __('Sign in with a 6-digit code from an app like Aegis, 2FAS, Google Authenticator or your password manager, even when email does not work.') }}</x-slot>

        @if (count($recoveryCodes))
            <div class="mb-4 rounded-lg border border-warning-300 bg-warning-50 p-4 dark:border-warning-500/40 dark:bg-warning-500/10">
                <p class="mb-3 text-sm font-medium">{{ __('Your recovery codes. Each works once if you lose your phone. Print them or write them down and keep them in your emergency folder. They are shown only now.') }}</p>
                <ul class="grid grid-cols-2 gap-2 font-mono text-base">
                    @foreach ($recoveryCodes as $code)
                        <li>{{ $code }}</li>
                    @endforeach
                </ul>
                <x-filament::button class="mt-4" color="gray" size="sm" icon="heroicon-o-printer" x-on:click="window.print()">
                    {{ __('Print') }}
                </x-filament::button>
            </div>
        @endif

        @if (auth()->user()->hasTotpEnabled())
            <p class="mb-4 text-sm">
                {{ __('Set up on :date. :count recovery codes left.', ['date' => auth()->user()->totp_confirmed_at->isoFormat('LL'), 'count' => count(auth()->user()->totp_recovery_codes ?? [])]) }}
            </p>
            <div class="flex flex-wrap gap-3">
                <x-filament::button color="gray" size="sm" wire:click="regenerateRecoveryCodes" wire:confirm="{{ __('Create new recovery codes? The old ones stop working.') }}">
                    {{ __('New recovery codes') }}
                </x-filament::button>
                <x-filament::button color="danger" outlined size="sm" wire:click="disableTotp" wire:confirm="{{ __('Remove the authenticator app?') }}">
                    {{ __('Remove') }}
                </x-filament::button>
            </div>
        @elseif ($totpQrCode)
            <ol class="mb-4 list-decimal space-y-2 ps-5 text-sm">
                <li>{{ __('Scan this QR code with your authenticator app.') }}</li>
                <li>{{ __('Enter the 6-digit code the app shows.') }}</li>
            </ol>
            <img src="{{ $totpQrCode }}" alt="{{ __('QR code for the authenticator app') }}" class="mb-3 size-48 rounded bg-white p-2">
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Cannot scan? Enter this key instead:') }}
                <span class="font-mono text-gray-950 select-all dark:text-white">{{ $totpSecretForDisplay }}</span>
            </p>
            <form wire:submit="confirmTotpSetup" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                <div class="flex-1">
                    <x-filament::input.wrapper :valid="! $errors->has('totpCode')">
                        <x-filament::input type="text" wire:model="totpCode" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required :placeholder="__('6-digit code')" />
                    </x-filament::input.wrapper>
                    @error('totpCode')
                        <p class="mt-2 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                    @enderror
                </div>
                <x-filament::button type="submit">{{ __('Confirm') }}</x-filament::button>
                <x-filament::button color="gray" wire:click="cancelTotpSetup">{{ __('Cancel') }}</x-filament::button>
            </form>
        @else
            <x-filament::button icon="heroicon-o-device-phone-mobile" wire:click="startTotpSetup">
                {{ __('Set up authenticator app') }}
            </x-filament::button>
        @endif
    </x-filament::section>
</x-filament-panels::page>
