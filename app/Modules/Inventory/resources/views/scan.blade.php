<x-filament-panels::page>
    @vite('resources/js/scanner.js')

    <div
        x-data="{
            state: 'starting',
            controls: null,
            async start() {
                this.state = 'starting';
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
                    // A code typed in the meantime already shows its product.
                    if ($wire.productId) {
                        this.stop();
                        return;
                    }
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
        x-init="start(); $wire.$watch('productId', (id) => id ? stop() : start())"
        x-on:livewire:navigating.window="stop()"
        x-on:pagehide.window="stop()"
        class="space-y-4"
    >
        @if ($product)
            {{-- A scanned product in stock: take out of a batch, or add one. --}}
            <x-filament::section wire:key="found-{{ $product->id }}">
                <x-slot name="heading">
                    <a href="{{ $productUrl }}" class="hover:underline">{{ $product->name }}</a>
                </x-slot>
                <x-slot name="description">
                    {{ collect([$product->brand, $product->packageLabel(), __($product->category->name)])->filter()->implode(' · ') }}
                </x-slot>

                @if ($batches->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Nothing left in stock.') }}</p>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('In stock, earliest best-before date first:') }}</p>
                    <ul class="mt-2 divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($batches as $batch)
                            @php($status = $batch->status($eatSoonDays))
                            <li class="flex items-center justify-between gap-3 py-3" wire:key="batch-{{ $batch->id }}">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="font-medium">{{ $batch->quantityLabel() }}</span>
                                        <x-filament::badge :color="$status->getColor()" :tooltip="$status->getLabel()">
                                            {{ $batch->best_before?->locale(app()->getLocale())->isoFormat('L') ?? __('No date') }}
                                        </x-filament::badge>
                                    </div>
                                    @if ($batch->location || $batch->note)
                                        <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ collect([$batch->location?->name, $batch->note])->filter()->implode(' · ') }}</p>
                                    @endif
                                </div>
                                <div class="shrink-0">{{ ($this->takeOutAction)(['batch' => $batch->id]) }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-4 flex flex-wrap gap-3">
                    <x-filament::button tag="a" :href="$addUrl" color="gray" icon="heroicon-o-plus-circle">{{ __('Add batch') }}</x-filament::button>
                    <x-filament::button wire:click="scanNext" color="gray" icon="heroicon-o-qr-code">{{ __('Scan next') }}</x-filament::button>
                </div>
            </x-filament::section>
        @endif

        <div wire:ignore.self class="relative mx-auto aspect-[4/3] w-full max-w-lg overflow-hidden rounded-xl bg-gray-950" x-show="! $wire.productId && (state === 'scanning' || state === 'starting' || state === 'found')">
            <video x-ref="video" class="h-full w-full object-cover" muted playsinline></video>
            {{-- Aiming guide --}}
            <div class="pointer-events-none absolute inset-x-8 top-1/2 h-24 -translate-y-1/2 rounded-lg border-2 border-white/80"></div>
            <p class="absolute inset-x-0 bottom-3 text-center text-sm text-white" x-show="state === 'scanning'">{{ __('Hold the barcode inside the frame.') }}</p>
            <p class="absolute inset-x-0 bottom-3 text-center text-sm text-white" x-show="state === 'found'" x-cloak>{{ __('Found, one moment …') }}</p>
        </div>

        <div x-show="! $wire.productId && state === 'insecure'" x-cloak class="rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm dark:border-warning-500/40 dark:bg-warning-500/10">
            {{ __('The camera only works when Holdout is opened over HTTPS. Type the barcode below, or set up HTTPS (see the handbook, Installation).') }}
        </div>
        <div x-show="! $wire.productId && state === 'denied'" x-cloak class="rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm dark:border-warning-500/40 dark:bg-warning-500/10">
            {{ __('Camera access was not allowed. Allow it in the browser settings for this page, or type the barcode below.') }}
        </div>
        <div x-show="! $wire.productId && (state === 'nocamera' || state === 'unsupported')" x-cloak class="rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm dark:border-warning-500/40 dark:bg-warning-500/10">
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
