@props(['title' => 'Masuk'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — SI-PPG</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-surface-page">
    <x-toast-container />
    <x-flash-toast />

    <div class="grid min-h-screen lg:grid-cols-2">
        <div class="hidden flex-col justify-between bg-sidebar p-12 text-white lg:flex">
            <div class="flex items-center gap-2">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15 text-xl font-bold text-brand-yellow">S</div>
                <span class="text-lg font-semibold">SI-PPG</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold leading-tight">Sistem Informasi<br>Pembinaan Generus</h1>
                <p class="mt-4 max-w-sm text-white/80">
                    Satu sumber data untuk pendataan dan pelaporan rutin PPG — sensus, presensi,
                    jurnal harian, musyawaroh, dan kegiatan, dari tingkat Kelompok sampai Daerah.
                </p>
            </div>
            <p class="text-sm text-white/60">&copy; {{ date('Y') }} SI-PPG</p>
        </div>

        <div class="flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-sm">
                {{ $slot }}
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
