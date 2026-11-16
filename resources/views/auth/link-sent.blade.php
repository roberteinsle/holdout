<x-guest-layout :title="__('Check your email')">
    <h1 class="mb-2 text-lg font-semibold">{{ __('Check your email') }}</h1>
    <p class="text-fg-muted">
        {{ __('If an account exists for this address, we have sent a sign-in link. It is valid for :minutes minutes and works once.', ['minutes' => $minutes]) }}
    </p>
    <a href="{{ route('login') }}" class="mt-6 inline-block text-sm font-medium text-primary underline">{{ __('Back to sign-in') }}</a>
</x-guest-layout>
