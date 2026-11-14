<x-guest-layout :title="__('Set up your household')">
    <h1 class="mb-1 text-lg font-semibold">{{ __('Set up your household') }}</h1>
    <p class="mb-5 text-sm text-fg-muted">{{ __('You are the first person here, so you become the owner. You can invite your family afterwards.') }}</p>

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf
        <x-field name="household" :label="__('Household name')" :placeholder="__('e.g. The Millers')" required maxlength="100" />
        <x-field name="name" :label="__('Your name')" autocomplete="name" required maxlength="100" />
        <x-field name="email" type="email" :label="__('Email')" autocomplete="email" required maxlength="255" />
        <x-primary-button>{{ __('Create household') }}</x-primary-button>
    </form>
</x-guest-layout>
