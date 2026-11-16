@php
    /** @var \Illuminate\Support\Collection<string, \App\Support\ShoppingItem> $items */
    $format = \App\Support\ShoppingList::format(...);
@endphp

<x-filament-panels::page>
    @if ($people === 0)
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Enter how many adults and children live in the household to see the amounts.') }}</p>
            <x-filament::link :href="$householdUrl" class="mt-2">{{ __('Household') }}</x-filament::link>
        </x-filament::section>
    @elseif ($items->isEmpty())
        <x-filament::section>
            <div class="hd-level-enough flex items-center gap-3">
                <x-filament::icon icon="heroicon-o-check-circle" class="hd-level-text h-8 w-8 shrink-0" />
                <p class="text-base font-semibold">{{ __('Nothing to buy: your supply reaches the target of :days days.', ['days' => $targetDays]) }}</p>
            </div>
        </x-filament::section>
    @else
        <x-filament::section :description="__('What is missing to reach :days days for your household, rounded up.', ['days' => $targetDays])">
            <ul class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($items as $item)
                    @php($category = $item->status->target->category)
                    @php($level = $item->status->level())
                    <li class="hd-level-{{ $level->value }} py-3">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="inline-flex min-w-0 items-center gap-1.5 font-medium">
                                <x-filament::icon :icon="$level->getIcon()" class="hd-level-text h-5 w-5 shrink-0" :label="$level->getLabel()" />
                                <span class="truncate">{{ __($category->name) }}</span>
                            </span>
                            <span class="shrink-0 text-lg font-semibold tabular-nums">{{ $format($item->amount, $category->unit) }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Have :stock of :target', ['stock' => $format($item->status->stock, $category->unit), 'target' => $format($item->status->target->total, $category->unit)]) }}
                            · {{ trans_choice(':count day short|:count days short', $item->daysShort) }}
                        </p>
                        @if ($item->status->expired > 0)
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Plus :amount expired on the shelf, not counted.', ['amount' => $format($item->status->expired, $category->unit)]) }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-filament::section>

        {{-- Share sheet on phones, clipboard otherwise; the text stays selectable as a fallback (no HTTPS). --}}
        <div x-data="{ copied: false }">
        <x-filament::section :heading="__('Share')">
            <textarea x-ref="text" readonly rows="{{ $items->count() + 2 }}" class="w-full rounded-lg border-gray-200 bg-gray-50 text-sm dark:border-white/10 dark:bg-white/5" aria-label="{{ __('Shopping list') }}">{{ $text }}</textarea>
            <div class="mt-3 flex flex-wrap gap-3">
                <x-filament::button
                    icon="heroicon-o-share"
                    x-show="navigator.share"
                    x-cloak
                    x-on:click="navigator.share({ text: $refs.text.value }).catch(() => {})"
                >{{ __('Share') }}</x-filament::button>
                <x-filament::button
                    color="gray"
                    icon="heroicon-o-clipboard-document"
                    x-on:click="navigator.clipboard ? navigator.clipboard.writeText($refs.text.value).then(() => copied = true) : ($refs.text.select(), document.execCommand('copy'), copied = true)"
                >
                    <span x-show="! copied">{{ __('Copy') }}</span>
                    <span x-show="copied" x-cloak>{{ __('Copied') }}</span>
                </x-filament::button>
            </div>
        </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
