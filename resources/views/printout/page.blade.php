<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('Emergency supply') }} · {{ $household->name }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    {{-- Black and white, A4; status is written out, never shown by colour alone. --}}
    <style>
        @page { size: A4; margin: 14mm 12mm 16mm; @bottom-right { content: counter(page) " / " counter(pages); font: 8pt system-ui, sans-serif; } }
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; color: #000; font: 10pt/1.35 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        main { max-width: 186mm; margin: 0 auto; padding: 16px; }
        header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 12px; }
        header img { height: 28px; }
        h1 { font-size: 16pt; margin: 4px 0 0; }
        h2 { font-size: 12.5pt; margin: 18px 0 6px; padding-bottom: 2px; border-bottom: 1px solid #000; break-after: avoid; }
        h3 { font-size: 10.5pt; margin: 12px 0 4px; break-after: avoid; }
        p { margin: 4px 0; }
        .lead { font-size: 11pt; font-weight: 600; }
        .small, .muted { font-size: 8.5pt; color: #444; }
        .meta { text-align: right; font-size: 8.5pt; color: #444; }
        table { width: 100%; border-collapse: collapse; margin: 4px 0 6px; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
        th, td { border: 1px solid #777; padding: 3px 5px; text-align: left; vertical-align: top; }
        th { font-size: 8.5pt; font-weight: 600; background: #eee; }
        .num { text-align: right; white-space: nowrap; }
        .date { white-space: nowrap; }
        .strong { font-weight: 700; }
        table.fixed { table-layout: fixed; }
        table.fixed .num { width: 26mm; }
        table.fixed .date { width: 50mm; white-space: normal; }
        .tally { width: 28mm; }
        .tag { display: inline-block; margin-left: 3px; padding: 0 4px; border: 1px solid #000; border-radius: 3px; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; background: #000; color: #fff; }
        .tag-light { background: #fff; color: #000; }
        .offline { margin-bottom: 12px; padding: 8px 10px; border: 2px solid #000; border-radius: 6px; background: #f4f1ea; }
        .offline[hidden] { display: none; }
        footer { margin-top: 16px; padding-top: 6px; border-top: 1px solid #000; font-size: 8.5pt; color: #444; }
        .toolbar { position: sticky; top: 0; display: flex; gap: 8px; justify-content: flex-end; padding: 8px 16px; background: #f4f1ea; border-bottom: 1px solid #bcae8b; }
        .toolbar a, .toolbar button { font: 600 11pt system-ui, sans-serif; padding: 8px 14px; border-radius: 8px; border: 1px solid #bcae8b; background: #fff; color: #000; text-decoration: none; cursor: pointer; }
        .toolbar button { background: #4A5342; border-color: #4A5342; color: #fff; }
        @media print { .toolbar { display: none; } main { padding: 0; max-width: none; } th { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .tag { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
        @media screen and (max-width: 600px) { body { font-size: 9.5pt; } main { padding: 12px; } table.fixed { table-layout: auto; } table.fixed .num, table.fixed .date { width: auto; } .tally { width: 14mm; } .date { white-space: normal; } .num { white-space: normal; } }
    </style>
</head>
<body>
    <nav class="toolbar">
        <a href="{{ $backUrl }}">{{ __('Back') }}</a>
        <button type="button" onclick="window.print()">{{ __('Print') }}</button>
    </nav>
    <main>
        {{-- Switched on by the service worker when it serves this page as the offline copy (#14). --}}
        <div class="offline" data-offline-banner hidden>
            <strong>{{ __('Offline copy') }}</strong> · {{ __('The server cannot be reached. This is the copy saved on this device; it shows the stock as of the date above and cannot be changed.') }}
        </div>
        <header>
            <div>
                <img src="{{ asset('logo.svg') }}" alt="Holdout">
                <h1>{{ __('Emergency supply') }} · {{ $household->name }}</h1>
            </div>
            <div class="meta">{{ __('As of :date', ['date' => $printedAt]) }}</div>
        </header>

        @foreach ($sections as $section)
            {!! $section !!}
        @endforeach

        <footer>{{ __('Paper copy for a power or network outage. It shows the stock at the time of printing; print a new one after shopping.') }}</footer>
    </main>
</body>
</html>
