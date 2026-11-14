<x-guest-layout :title="__('Sign in with authenticator app')">
    <h1 class="mb-1 text-lg font-semibold">{{ __('Sign in with authenticator app') }}</h1>
    <p class="mb-5 text-sm text-stone-600">{{ __('Enter the 6-digit code from your authenticator app, or one of your recovery codes.') }}</p>

    <form method="POST" action="{{ route('login.code.store') }}" class="space-y-4">
        @csrf
        <x-field name="email" type="email" :label="__('Email')" autocomplete="email" required maxlength="255" />
        <x-field name="code" :label="__('Code')" autocomplete="one-time-code" inputmode="text" required maxlength="20" />
        <x-primary-button>{{ __('Sign in') }}</x-primary-button>
    </form>

    <a href="{{ route('login') }}" class="mt-6 inline-block text-sm font-medium text-amber-700 underline">{{ __('Back to sign-in') }}</a>
</x-guest-layout>
