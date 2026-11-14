<x-mail::message>
# {{ __('Join :household', ['household' => $invitation->household->name]) }}

{{ __(':name invited you to keep your family emergency supply together in :app.', ['name' => $invitation->inviter?->name ?? config('app.name'), 'app' => config('app.name')]) }}

<x-mail::button :url="$url">
{{ __('Accept invitation') }}
</x-mail::button>

{{ __('The invitation is valid for :days days.', ['days' => $days]) }}
</x-mail::message>
