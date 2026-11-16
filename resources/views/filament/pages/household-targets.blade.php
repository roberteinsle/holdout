@php
    /** @var \Illuminate\Support\Collection<string, \App\Support\CategoryStatus> $status */
    /** @var ?\App\Support\CategoryStatus $limitedBy */
    $format = fn (float $value, string $unit): string => \Illuminate\Support\Number::format($value, maxPrecision: 1, locale: app()->getLocale()).' '.$unit;
    $overall = \App\Support\StockLevel::for($daysOfSupply, $days);
@endphp

@if ($people === 0)
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Enter how many adults and children live in the household to see the amounts.') }}</p>
@else
    {{-- Summary: how long the whole supply lasts (the category that runs out first). --}}
    <div class="hd-level-{{ $overall->value }} mb-4 flex items-start gap-3 rounded-lg bg-gray-50 p-3 dark:bg-white/5">
        <x-filament::icon :icon="$overall->getIcon()" class="hd-level-text mt-0.5 h-6 w-6 shrink-0" />
        <div>
            <p class="text-base font-semibold">
                {{ __('Your supply lasts :days of :target days.', ['days' => $daysOfSupply, 'target' => $days]) }}
            </p>
            @if ($limitedBy && $daysOfSupply < $days)
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Runs out first: :category', ['category' => __($limitedBy->target->category->name)]) }}</p>
            @endif
        </div>
    </div>

    <ul class="divide-y divide-gray-100 dark:divide-white/5">
        @foreach ($status as $item)
            @php($level = $item->level())
            @php($unit = $item->target->category->unit)
            <li class="hd-level-{{ $level->value }} py-3">
                <div class="flex items-baseline justify-between gap-3">
                    <span class="font-medium">{{ __($item->target->category->name) }}</span>
                    <span class="shrink-0 text-sm tabular-nums">
                        <span class="font-semibold">{{ $format($item->stock, $unit) }}</span>
                        <span class="text-gray-500 dark:text-gray-400">/ {{ $format($item->target->total, $unit) }}</span>
                    </span>
                </div>
                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $item->percent() }}" aria-label="{{ __($item->target->category->name) }}">
                    <div class="hd-level-bar h-full rounded-full" style="width: {{ $item->percent() }}%"></div>
                </div>
                <div class="mt-1.5 flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-xs">
                    <span class="hd-level-text inline-flex items-center gap-1 font-medium">
                        <x-filament::icon :icon="$level->getIcon()" class="h-4 w-4" />
                        {{ $level->getLabel() }} · {{ trans_choice(':count day|:count days', $item->days()) }}
                        @if ($item->missing() > 0)
                            · {{ __(':amount missing', ['amount' => $format($item->missing(), $unit)]) }}
                        @endif
                    </span>
                    <span class="text-gray-500 dark:text-gray-400">{{ __(':amount per day', ['amount' => $format($item->target->perDay, $unit)]) }}</span>
                </div>
                @if ($item->expired > 0)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Plus :amount expired, not counted.', ['amount' => $format($item->expired, $unit)]) }}</p>
                @endif
            </li>
        @endforeach
    </ul>
    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
        {{ __('Values per person and day from the BLE stock calculator (2,200 kcal). Children count like adults except for drinks.') }}
        {{ __('Only batches that have not expired count. 1 l counts as 1 kg.') }}
    </p>
@endif
