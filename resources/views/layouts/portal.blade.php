@props(['title' => 'Portal Orang Tua'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — SI-PPG</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#076B3B">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-surface-page pb-20">
    <x-toast-container />
    <x-flash-toast />

    <header class="no-print sticky top-0 z-20 flex h-16 items-center justify-between bg-topbar px-4 text-white shadow-sm">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15 text-sm font-bold text-brand-yellow">S</div>
            <span class="font-semibold">{{ $title }}</span>
        </div>
        <form method="POST" action="{{ route('portal.logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-1 text-sm text-white/85">
                <x-icon name="log-out" size="16" /> Keluar
            </button>
        </form>
    </header>

    <main class="mx-auto max-w-2xl px-4 py-6">
        {{ $slot }}
    </main>

    <nav class="no-print fixed inset-x-0 bottom-0 z-20 grid grid-cols-4 border-t border-border bg-white shadow-md">
        @php $active = fn (string $pattern) => request()->routeIs($pattern) ? 'text-brand-primary' : 'text-ink-muted'; @endphp
        <a href="{{ route('portal.dashboard') }}" class="flex flex-col items-center gap-1 py-2.5 text-xs {{ $active('portal.dashboard') }}">
            <x-icon name="home" size="20" /> Beranda
        </a>
        <a href="{{ route('portal.presensi') }}" class="flex flex-col items-center gap-1 py-2.5 text-xs {{ $active('portal.presensi') }}">
            <x-icon name="calendar-check" size="20" /> Presensi
        </a>
        <a href="{{ route('portal.jurnal') }}" class="flex flex-col items-center gap-1 py-2.5 text-xs {{ $active('portal.jurnal') }}">
            <x-icon name="book-open" size="20" /> Jurnal
        </a>
        <a href="{{ route('portal.notifikasi') }}" class="flex flex-col items-center gap-1 py-2.5 text-xs {{ $active('portal.notifikasi') }}">
            <x-icon name="bell" size="20" /> Notifikasi
        </a>
    </nav>

    @livewireScripts
</body>
</html>
