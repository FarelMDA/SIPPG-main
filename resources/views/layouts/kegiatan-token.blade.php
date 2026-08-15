<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Presensi Kegiatan — SI-PPG</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{-- Shell minimal TANPA navigasi apa pun (UIUX §3.3.3) — khusus P-KGT-TOKEN-01,
     diakses Generus lewat tautan token tanpa akun sama sekali. --}}
<body class="min-h-screen bg-surface-page">
    <x-toast-container />
    <x-flash-toast />

    <header class="bg-topbar px-4 py-4 text-white">
        <div class="mx-auto flex max-w-xl items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15 text-sm font-bold text-brand-yellow">S</div>
            <span class="font-semibold">SI-PPG — Presensi Kegiatan</span>
        </div>
    </header>

    <main class="mx-auto max-w-xl px-4 py-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
