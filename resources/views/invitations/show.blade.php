<x-guest-layout :title="__('Invitation')">
    @if ($invitation)
        <h1 class="mb-1 text-lg font-semibold">{{ __('Join :household', ['household' => $invitation->household->name]) }}</h1>
        <p class="mb-5 text-sm text-stone-600">
            {{ __(':name invited you (:email).', ['name' => $invitation->inviter?->name ?? config('app.name'), 'email' => $invitation->email]) }}
        </p>

        <form method="POST" action="{{ route('invitations.accept', $token) }}" class="space-y-4">
            @csrf
            <x-field name="name" :label="__('Your name')" :value="$invitation->name" autocomplete="name" required maxlength="100" />
            <x-primary-button>{{ __('Accept invitation') }}</x-primary-button>
        </form>
    @else
        <h1 class="mb-2 text-lg font-semibold">{{ __('This invitation no longer works') }}</h1>
        <p class="text-stone-600">{{ __('It may have expired or already been used. Ask for a new invitation.') }}</p>
        <a href="{{ route('login') }}" class="mt-6 inline-block text-sm font-medium text-amber-700 underline">{{ __('Go to sign-in') }}</a>
    @endif
</x-guest-layout>
