<x-guest-layout :title="__('Sign in')">
    <h1 class="mb-4 text-lg font-semibold">{{ __('Sign in') }}</h1>

    <button type="button" data-passkey-login
        class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg bg-stone-900 px-4 py-3 text-base font-semibold text-white hover:bg-stone-700 disabled:opacity-50">
        {{ __('Sign in with passkey') }}
    </button>
    <p data-passkey-error hidden class="mb-2 text-sm text-red-600">{{ __('Sign-in with passkey did not work. Use an email link instead.') }}</p>

    <div class="my-5 flex items-center gap-3 text-sm text-stone-400">
        <span class="h-px flex-1 bg-stone-200"></span>{{ __('or') }}<span class="h-px flex-1 bg-stone-200"></span>
    </div>

    <form method="POST" action="{{ route('login.send') }}" class="space-y-4">
        @csrf
        <x-field name="email" type="email" :label="__('Email')" autocomplete="email webauthn" required maxlength="255" />
        <x-primary-button>{{ __('Email me a sign-in link') }}</x-primary-button>
    </form>

    <a href="{{ route('login.code') }}" class="mt-5 block text-center text-sm font-medium text-amber-700 underline">
        {{ __('Sign in with authenticator app') }}
    </a>
</x-guest-layout>
