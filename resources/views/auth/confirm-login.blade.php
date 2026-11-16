<x-guest-layout :title="__('Sign in')">
    @if ($valid)
        <h1 class="mb-2 text-lg font-semibold">{{ __('Sign in') }}</h1>
        <p class="mb-6 text-fg-muted">{{ __('Tap the button to finish signing in on this device.') }}</p>
        <form method="POST" action="{{ route('login.consume', $token) }}">
            @csrf
            <x-primary-button>{{ __('Sign in now') }}</x-primary-button>
        </form>
    @else
        <h1 class="mb-2 text-lg font-semibold">{{ __('This link no longer works') }}</h1>
        <p class="text-fg-muted">{{ __('Sign-in links expire after a short time and work only once. Request a new one.') }}</p>
        <a href="{{ route('login') }}" class="mt-6 inline-block text-sm font-medium text-primary underline">{{ __('Request a new link') }}</a>
    @endif
</x-guest-layout>
