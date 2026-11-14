<x-mail::message>
# {{ __('Sign in to :app', ['app' => config('app.name')]) }}

{{ __('Hello :name,', ['name' => $user->name]) }}

{{ __('Use the button below to sign in. The link is valid for :minutes minutes and works once.', ['minutes' => $minutes]) }}

<x-mail::button :url="$url">
{{ __('Sign in') }}
</x-mail::button>

{{ __('If you did not ask for this, you can ignore this email.') }}
</x-mail::message>
