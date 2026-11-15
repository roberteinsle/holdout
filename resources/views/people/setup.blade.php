<x-guest-layout :title="__('Set up your household')">
    <h1 class="mb-1 text-lg font-semibold">{{ __('Set up your household') }}</h1>
    <p class="mb-5 text-sm text-fg-muted">{{ __('Holdout runs on your home network. Everyone at home can use it without signing in; you can add your family afterwards.') }}</p>

    <form method="POST" action="{{ route('setup.store') }}" class="space-y-4">
        @csrf
        <x-field name="household" :label="__('Household name')" :placeholder="__('e.g. The Millers')" required maxlength="100" />
        <x-field name="name" :label="__('Your name')" autocomplete="given-name" required maxlength="100" />
        <x-primary-button>{{ __('Create household') }}</x-primary-button>
    </form>
</x-guest-layout>
