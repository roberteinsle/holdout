<x-filament-panels::page>
    <x-filament::section :heading="__('Paper emergency copy')">
        <p class="text-sm">{{ __('When the power or the network is down, this app is gone too. Keep a printed copy with the supply: how long it lasts, how much the household needs per day, what to eat first and what is stored where.') }}</p>
        <ul class="mt-3 list-disc space-y-1 ps-5 text-sm text-gray-600 dark:text-gray-300">
            <li>{{ __('Print a new copy after shopping or at least every few months; it shows the date it was made.') }}</li>
            <li>{{ __('Keep it in a folder with the supply or your emergency documents.') }}</li>
            <li>{{ __('During an outage, make a mark under "Taken" for every package; update the app afterwards.') }}</li>
            <li>{{ __('On a phone, "Print" can also save the copy as a PDF.') }}</li>
        </ul>
    </x-filament::section>
    {{-- Offline copy (#14): the service worker (public/sw.js) keeps the printout on this device. --}}
    <x-filament::section :heading="__('Offline copy on this device')">
        <div
            x-data="{
                state: 'checking',
                savedAt: null,
                busy: false,
                ask(message) {
                    return new Promise((resolve) => {
                        const channel = new MessageChannel();
                        channel.port1.onmessage = (event) => resolve(event.data.savedAt);
                        navigator.serviceWorker.controller
                            ? navigator.serviceWorker.controller.postMessage(message, [channel.port2])
                            : navigator.serviceWorker.ready.then((registration) => registration.active.postMessage(message, [channel.port2]));
                    });
                },
                show(savedAt) {
                    this.savedAt = savedAt ? new Date(savedAt).toLocaleString(document.documentElement.lang, { dateStyle: 'short', timeStyle: 'short' }) : null;
                    this.state = savedAt ? 'saved' : 'missing';
                },
                async init() {
                    if (! ('serviceWorker' in navigator) || ! window.isSecureContext) { this.state = 'unsupported'; return; }
                    await navigator.serviceWorker.ready;
                    this.show(await this.ask('refresh'));
                },
                async update() {
                    this.busy = true;
                    this.show(await this.ask('refresh-now'));
                    this.busy = false;
                },
            }"
            class="space-y-3 text-sm"
        >
            <p>{{ __('This phone keeps the printout as an offline copy. If the server cannot be reached, for example in a power cut, Holdout opens this copy instead of the app.') }}</p>

            <p x-show="state === 'checking'" class="text-gray-500 dark:text-gray-400">{{ __('Checking…') }}</p>
            <p x-show="state === 'saved'" x-cloak class="hd-level-enough flex items-center gap-1.5 font-medium">
                <x-filament::icon icon="heroicon-o-check-circle" class="hd-level-text h-5 w-5" />
                <span>{{ __('Saved on this device:') }} <span x-text="savedAt"></span></span>
            </p>
            <p x-show="state === 'missing'" x-cloak class="hd-level-low flex items-center gap-1.5 font-medium">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="hd-level-text h-5 w-5" />
                <span>{{ __('No offline copy on this device yet.') }}</span>
            </p>
            <p x-show="state === 'unsupported'" x-cloak class="hd-level-low flex items-start gap-1.5 font-medium">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="hd-level-text h-5 w-5 shrink-0" />
                <span>{{ __('Not available here: the offline copy needs the HTTPS address of Holdout. Use the printout instead.') }}</span>
            </p>

            <div x-show="state === 'saved' || state === 'missing'" x-cloak>
                <x-filament::button color="gray" icon="heroicon-o-arrow-path" x-on:click="update()" x-bind:disabled="busy">{{ __('Update now') }}</x-filament::button>
            </div>

            <ul class="list-disc space-y-1 ps-5 text-gray-600 dark:text-gray-300">
                <li>{{ __('The copy updates itself every time you open Holdout on this phone.') }}</li>
                <li>{{ __('Add Holdout to the home screen so it opens like an app, also offline.') }}</li>
                <li>{{ __('Each phone keeps its own copy; open Holdout on every phone of the family once.') }}</li>
            </ul>
        </div>
    </x-filament::section>
</x-filament-panels::page>
