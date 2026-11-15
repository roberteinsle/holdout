<x-guest-layout :title="__('Who are you?')">
    <h1 class="mb-1 text-lg font-semibold">{{ __('Who are you?') }}</h1>
    <p class="mb-5 text-sm text-fg-muted">{{ __('This device remembers your choice. You can switch person at any time.') }}</p>

    <div class="space-y-2">
        @foreach ($people as $person)
            <form method="POST" action="{{ route('people.select', $person) }}">
                @csrf
                <button type="submit" class="w-full rounded-lg border border-line px-4 py-3 text-left text-base font-semibold hover:border-primary hover:bg-primary/10 focus:ring-2 focus:ring-primary/50 focus:outline-none">
                    {{ $person->name }}
                </button>
            </form>
        @endforeach
    </div>

    <details class="mt-6" @if ($errors->any()) open @endif>
        <summary class="cursor-pointer text-sm font-medium text-primary underline">{{ __('Not in the list? Add yourself') }}</summary>
        <form method="POST" action="{{ route('people.store') }}" class="mt-4 space-y-4">
            @csrf
            <x-field name="name" :label="__('Your name')" autocomplete="given-name" required maxlength="100" />
            <x-primary-button>{{ __('Add and continue') }}</x-primary-button>
        </form>
    </details>
</x-guest-layout>
