<x-guest-layout :title="__('Sign in')">
    <h1 class="mb-4 text-lg font-semibold">{{ __('Sign in') }}</h1>

    <button type="button" data-passkey-login
        class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-3 text-base font-semibold text-on-primary hover:bg-primary-hover disabled:opacity-50">
        {{ __('Sign in with passkey') }}
    </button>
    <p data-passkey-error hidden class="mb-2 text-sm text-danger">{{ __('Sign-in with passkey did not work. Use an email link instead.') }}</p>

    <div class="my-5 flex items-center gap-3 text-sm text-fg-muted">
        <span class="h-px flex-1 bg-line"></span>{{ __('or') }}<span class="h-px flex-1 bg-line"></span>
    </div>

    <form method="POST" action="{{ route('login.send') }}" class="space-y-4">
        @csrf
        <x-field name="email" type="email" :label="__('Email')" autocomplete="email webauthn" required maxlength="255" />
        <button type="submit" class="w-full rounded-lg border-2 border-primary px-4 py-3 text-base font-semibold text-primary hover:bg-primary/10 focus:ring-2 focus:ring-primary/50 focus:outline-none">
            {{ __('Email me a sign-in link') }}
        </button>
    </form>

    <a href="{{ route('login.code') }}" class="mt-5 block text-center text-sm font-medium text-primary underline">
        {{ __('Sign in with authenticator app') }}
    </a>
</x-guest-layout>
