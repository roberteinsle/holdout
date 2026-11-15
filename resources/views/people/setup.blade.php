<x-guest-layout :title="__('Set up your household')">
    <h1 class="mb-1 text-lg font-semibold">{{ __('Set up your household') }}</h1>
    <p class="mb-5 text-sm text-fg-muted">{{ __('Holdout runs on your home network. Everyone at home can use it without signing in; you can add your family afterwards.') }}</p>

    <form method="POST" action="{{ route('setup.store') }}" class="space-y-4">
        @csrf
        <x-field name="household" :label="__('Household name')" :placeholder="__('e.g. The Millers')" required maxlength="100" />
        <x-field name="name" :label="__('Your name')" autocomplete="given-name" required maxlength="100" />

        <fieldset>
            <legend class="mb-1 text-sm font-medium">{{ __('Who is the supply for?') }}</legend>
            <div class="grid grid-cols-2 gap-3">
                <x-field name="adults" type="number" :label="__('Adults')" value="2" min="1" max="30" required inputmode="numeric" />
                <x-field name="children" type="number" :label="__('Children up to 12')" value="0" min="0" max="30" required inputmode="numeric" />
            </div>
            <p class="mt-1 text-sm text-fg-muted">{{ __('Everyone who lives in the household, including small children. You can change this later.') }}</p>
        </fieldset>

        <x-primary-button>{{ __('Create household') }}</x-primary-button>
    </form>
</x-guest-layout>
