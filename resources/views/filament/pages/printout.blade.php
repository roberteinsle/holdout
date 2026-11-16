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
</x-filament-panels::page>
