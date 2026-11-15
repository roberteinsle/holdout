@php
    /** @var \Illuminate\Support\Collection<string, \App\Support\SupplyTarget> $targets */
    $format = fn (float $value, string $unit): string => \Illuminate\Support\Number::format($value, maxPrecision: 1, locale: app()->getLocale()).' '.$unit;
@endphp

@if ($people === 0)
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Enter how many adults and children live in the household to see the amounts.') }}</p>
@else
    <div class="-mx-2 overflow-x-auto">
        <table class="w-full text-start text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-gray-500 dark:border-white/10 dark:text-gray-400">
                    <th class="px-2 py-2 text-start font-medium">{{ __('Category') }}</th>
                    <th class="px-2 py-2 text-end font-medium">{{ __('Per day') }}</th>
                    <th class="px-2 py-2 text-end font-medium">{{ __('For :days days', ['days' => $days]) }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($targets as $target)
                    <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                        <td class="px-2 py-2">{{ __($target->category->name) }}</td>
                        <td class="px-2 py-2 text-end tabular-nums text-gray-500 dark:text-gray-400">{{ $format($target->perDay, $target->category->unit) }}</td>
                        <td class="px-2 py-2 text-end font-semibold tabular-nums">{{ $format($target->total, $target->category->unit) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
        {{ __('Values per person and day from the BLE stock calculator (2,200 kcal). Children count like adults except for drinks.') }}
    </p>
@endif
