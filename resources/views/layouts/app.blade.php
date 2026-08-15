@props(['title' => 'SI-PPG'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — SI-PPG</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#076B3B">
    @if(session('sync_token'))
        <meta name="sync-token" content="{{ session('sync_token') }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-surface-page">
    <x-toast-container />
    <x-flash-toast />

    @include('layouts.partials.sidebar')
    @include('layouts.partials.topbar', ['title' => $title])

    <main class="min-h-screen px-4 py-6 lg:pl-[304px] lg:pr-8">
        <div class="mx-auto max-w-[1600px]">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
</body>
</html>
