<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title ?? config('app.name') }}</title>
    @include('partials.head-icons')
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-page text-fg antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-sm flex-col justify-center px-4 py-10">
        <div class="mb-8 text-center">
            <h1>
                <picture>
                    <source srcset="{{ asset('logo-dark.svg') }}" media="(prefers-color-scheme: dark)">
                    <img src="{{ asset('logo.svg') }}" alt="{{ config('app.name') }}" class="mx-auto h-12 w-auto">
                </picture>
            </h1>
            <p class="mt-1 text-sm text-fg-muted">{{ __('Your family emergency supply') }}</p>
        </div>

        <div class="rounded-2xl border border-line bg-card p-6 shadow-sm">
            {{ $slot }}
        </div>
    </main>
</body>
</html>
