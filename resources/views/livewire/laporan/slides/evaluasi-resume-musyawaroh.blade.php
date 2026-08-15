<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
    <div>
        <h3 class="mb-2 font-semibold text-ink-primary">Evaluasi Bulan Lalu</h3>
        <ul class="list-inside list-disc space-y-1 text-sm">
            @forelse(($data['evaluasi_bulan_lalu'] ?? []) as $item)
                <li>{{ $item }}</li>
            @empty
                <li class="list-none text-ink-muted">Belum ada notulen bulan lalu.</li>
            @endforelse
        </ul>
    </div>
    <div>
        <h3 class="mb-2 font-semibold text-ink-primary">Resume Bulan Ini</h3>
        <ul class="list-inside list-disc space-y-1 text-sm">
            @forelse(($data['resume_bulan_ini'] ?? []) as $item)
                <li>{{ $item }}</li>
            @empty
                <li class="list-none text-ink-muted">Belum ada notulen bulan ini.</li>
            @endforelse
        </ul>
    </div>
</div>
