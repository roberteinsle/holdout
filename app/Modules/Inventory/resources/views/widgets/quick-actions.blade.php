<x-filament-widgets::widget>
    <div class="grid grid-cols-2 gap-3">
        <x-filament::button :href="$scanUrl" tag="a" size="xl" icon="heroicon-o-qr-code" class="w-full">
            {{ __('Scan') }}
        </x-filament::button>
        <x-filament::button :href="$addBatchUrl" tag="a" size="xl" color="gray" icon="heroicon-o-plus" class="w-full">
            {{ __('Add batch') }}
        </x-filament::button>
    </div>
</x-filament-widgets::widget>
