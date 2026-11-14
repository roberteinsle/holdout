<x-filament-panels::page>
    @if ($welcome && $this->getPasskeys()->isEmpty())
        <x-filament::section>
            <x-slot name="heading">{{ __('Welcome to :app!', ['app' => config('app.name')]) }}</x-slot>
            {{ __('Set up a passkey now, and next time you sign in with your fingerprint, face or device PIN instead of waiting for an email.') }}
        </x-filament::section>
    @endif

    <x-filament::section
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
</x-filament-panels::page>
