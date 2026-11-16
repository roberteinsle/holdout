<x-filament-panels::page>
    @vite('resources/js/scanner.js')

    <div
        x-data="{
            state: 'starting',
            controls: null,
            async start() {
                // The scanner script is a module and may load after Alpine: wait up to 15 s.
                for (let i = 0; i < 300 && ! window.holdoutScanner; i++) {
                    await new Promise((resolve) => setTimeout(resolve, 50));
                }

                // unavailableReason() returns null when the camera can be used.
                const reason = window.holdoutScanner ? window.holdoutScanner.unavailableReason() : 'unsupported';
                if (reason) {
                    this.state = reason;
                    return;
                }

                try {
                    this.controls = await window.holdoutScanner.start(this.$refs.video, (code) => {
                        this.state = 'found';
                        this.stop();
                        $wire.scanned(code);
                    });
                    this.state = 'scanning';
                } catch (error) {
                    console.warn('Holdout scanner:', error);
                    this.state = error?.name === 'NotAllowedError' ? 'denied' : 'nocamera';
                }
            },
            stop() {
                this.controls?.stop();
                this.controls = null;
            },
        }"
        x-init="start()"
        x-on:livewire:navigating.window="stop()"
        x-on:pagehide.window="stop()"
        class="space-y-4"
    >
        <div class="relative mx-auto aspect-[4/3] w-full max-w-lg overflow-hidden rounded-xl bg-gray-950" x-show="state === 'scanning' || state === 'starting' || state === 'found'">
            <video x-ref="video" class="h-full w-full object-cover" muted playsinline></video>
            {{-- Aiming guide --}}
            <div class="pointer-events-none absolute inset-x-8 top-1/2 h-24 -translate-y-1/2 rounded-lg border-2 border-white/80"></div>
            <p class="absolute inset-x-0 bottom-3 text-center text-sm text-white" x-show="state === 'scanning'">{{ __('Hold the barcode inside the frame.') }}</p>
            <p class="absolute inset-x-0 bottom-3 text-center text-sm text-white" x-show="state === 'found'" x-cloak>{{ __('Found, one moment …') }}</p>
        </div>

        <div x-show="state === 'insecure'" x-cloak class="rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm dark:border-warning-500/40 dark:bg-warning-500/10">
            {{ __('The camera only works when Holdout is opened over HTTPS. Type the barcode below, or set up HTTPS (see the handbook, Installation).') }}
        </div>
        <div x-show="state === 'denied'" x-cloak class="rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm dark:border-warning-500/40 dark:bg-warning-500/10">
            {{ __('Camera access was not allowed. Allow it in the browser settings for this page, or type the barcode below.') }}
        </div>
        <div x-show="state === 'nocamera' || state === 'unsupported'" x-cloak class="rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm dark:border-warning-500/40 dark:bg-warning-500/10">
            {{ __('No camera is available on this device. Type the barcode below.') }}
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('Type the barcode') }}</x-slot>
            <form wire:submit="scanned" class="flex flex-col gap-3 sm:flex-row">
                <x-filament::input.wrapper class="flex-1">
                    <x-filament::input type="text" wire:model="code" inputmode="numeric" autocomplete="off" maxlength="20" required :placeholder="__('e.g. 4006381333931')" />
                </x-filament::input.wrapper>
                <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">{{ __('Search') }}</x-filament::button>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
