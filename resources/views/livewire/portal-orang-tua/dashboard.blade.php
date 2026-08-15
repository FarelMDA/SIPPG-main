<div>
    @include('livewire.portal-orang-tua.partials.pemilih-anak')

    @if(!$anak)
        <x-empty-state title="Belum ada anak tertaut" description="Hubungi Sekretaris KBM bila ini tidak sesuai." />
    @else
        <div class="mb-4">
            <h1 class="text-xl font-semibold text-ink-primary">{{ $anak->nama }}</h1>
            <p class="text-sm text-ink-muted">{{ $anak->kelas->nama }} — {{ $anak->kelas->kelompok->nama }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-card padding="p-5">
                <p class="text-sm text-ink-muted">Kehadiran Bulan Ini</p>
                <p class="mt-1 text-3xl font-bold text-ink-primary">{{ $ringkasanKehadiran !== null ? $ringkasanKehadiran.'%' : '—' }}</p>
            </x-card>
            <x-card padding="p-5">
                <p class="text-sm text-ink-muted">Materi Terakhir</p>
                @if($materiTerakhir)
                    <p class="mt-1 text-sm font-medium text-ink-primary">{{ $materiTerakhir->tanggal->format('d/m/Y') }}</p>
                    <x-status-badge :status="$materiTerakhir->realisasi_status" />
                @else
                    <p class="mt-1 text-sm text-ink-muted">Belum ada data</p>
                @endif
            </x-card>
        </div>
    @endif
</div>
