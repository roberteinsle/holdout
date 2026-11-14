<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/passkeys.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-sm flex-col justify-center px-4 py-10">
        <div class="mb-8 text-center">
            <div class="text-2xl font-semibold tracking-tight">{{ config('app.name') }}</div>
            <p class="mt-1 text-sm text-stone-500">{{ __('Your family emergency supply') }}</p>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            {{ $slot }}
        </div>
    </main>
</body>
</html>
