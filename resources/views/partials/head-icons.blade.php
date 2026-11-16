{{-- Favicons, app icons and the web app manifest (files in public/, see handbuch/Design.md). --}}
<meta name="theme-color" content="#4A5342">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('manifest.json') }}">
@auth
    {{-- Offline copy (#14): the service worker keeps the printout on this device (needs HTTPS or localhost). --}}
    <script>
        if ('serviceWorker' in navigator && window.isSecureContext) {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).then(() => navigator.serviceWorker.ready).then((registration) => {
                registration.active?.postMessage('refresh');
            }).catch(() => {});
        }
    </script>
@endauth
