<div class="card p-6 text-center">
    <p class="text-sm text-ink-muted">Jumlah Hadir</p>
    <p class="mt-1 text-4xl font-bold text-brand-primary">{{ $data['hadir'] ?? 0 }}</p>
    <p class="mt-2 text-sm text-ink-muted">
        Total KK:
        @if(is_null($data['total_kk'] ?? null))
            <span class="italic">Data KK belum tersedia</span>
        @else
            {{ $data['total_kk'] }}
        @endif
    </p>
</div>
