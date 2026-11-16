<x-filament-widgets::widget>
    <x-filament::section :heading="__('Supply')">
        <x-slot name="afterHeader">
            <x-filament::link :href="$detailsUrl" size="sm">{{ __('Details') }}</x-filament::link>
        </x-slot>

        @if ($people === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Enter how many adults and children live in the household to see the amounts.') }}</p>
        @else
            <div class="hd-level-{{ $overall->value }} flex items-center gap-3">
                <x-filament::icon :icon="$overall->getIcon()" class="hd-level-text h-8 w-8 shrink-0" />
                <div>
                    <p class="text-lg font-semibold">{{ __('Your supply lasts :days of :target days.', ['days' => $daysOfSupply, 'target' => $target]) }}</p>
                    @if ($limitedBy && $daysOfSupply < $target)
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Runs out first: :category', ['category' => __($limitedBy->target->category->name)]) }}</p>
                    @endif
                </div>
            </div>

            <ul class="mt-4 space-y-2.5">
                @foreach ($status as $item)
                    @php($level = $item->level())
                    <li class="hd-level-{{ $level->value }}">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="inline-flex min-w-0 items-center gap-1.5">
                                <x-filament::icon :icon="$level->getIcon()" class="hd-level-text h-4 w-4 shrink-0" :label="$level->getLabel()" />
                                <span class="truncate">{{ __($item->target->category->name) }}</span>
                            </span>
                            <span class="shrink-0 tabular-nums text-gray-500 dark:text-gray-400">{{ $item->percent() }} % · {{ trans_choice(':count day|:count days', $item->days()) }}</span>
                        </div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                            <div class="hd-level-bar h-full rounded-full" style="width: {{ $item->percent() }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
