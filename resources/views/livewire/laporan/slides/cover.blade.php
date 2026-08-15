<div class="flex h-full flex-col items-center justify-center text-center">
    <p class="text-sm uppercase tracking-wide text-ink-muted">Laporan Bulanan</p>
    <h1 class="mt-2 text-3xl font-bold text-ink-primary">{{ $data['kelompok'] ?? '—' }}</h1>
    <p class="mt-2 text-lg text-ink-secondary">Periode {{ $data['periode'] ?? '—' }}</p>
</div>
