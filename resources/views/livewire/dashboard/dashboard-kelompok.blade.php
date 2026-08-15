<div>
    <x-page-header title="Dashboard Kelompok" />

    @if(auth('web')->user()->hasRole('pjp-desa'))
        <x-info-banner class="mb-6">Fitur ringkasan tingkat Desa akan tersedia pada Fase 2 — saat ini hanya menampilkan daftar Kelompok.</x-info-banner>
    @endif

    @if($kelompokOptions->count() > 1)
        <div class="no-print mb-6 max-w-xs">
            <x-select label="Kelompok" name="kelompokPilihan" wire:model.live="kelompokPilihan" :options="$kelompokOptions" />
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-card padding="p-5">
            <p class="text-sm text-ink-muted">Kehadiran Bulan Ini</p>
            <p class="mt-1 text-3xl font-bold text-ink-primary">{{ $persentaseKehadiran !== null ? $persentaseKehadiran.'%' : '—' }}</p>
        </x-card>
        <x-card padding="p-5">
            <p class="text-sm text-ink-muted">Total Generus Aktif</p>
            <p class="mt-1 text-3xl font-bold text-ink-primary">{{ $totalGenerus }}</p>
        </x-card>
        <x-card padding="p-5" class="sm:col-span-2 lg:col-span-2">
            <p class="mb-2 text-sm text-ink-muted">Sensus per Kategori (Bulan Ini)</p>
            <div class="flex flex-wrap gap-2">
                @forelse($sensusTerbaru as $kategori => $jumlah)
                    <x-badge variant="info">{{ $kategori }}: {{ $jumlah }}</x-badge>
                @empty
                    <span class="text-sm text-ink-muted">Snapshot bulan ini belum tersedia.</span>
                @endforelse
            </div>
        </x-card>
    </div>

    <x-card class="mb-6">
        <h2 class="mb-4 text-lg font-semibold text-ink-primary">Kegiatan Mendatang</h2>
        @if($kegiatanMendatang->isEmpty())
            <p class="text-sm text-ink-muted">Tidak ada Kegiatan terjadwal dalam waktu dekat.</p>
        @else
            <ul class="space-y-2">
                @foreach($kegiatanMendatang as $item)
                    <li class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <x-status-badge :status="$item['kegiatan']->tingkat" />
                            <span class="text-ink-primary">{{ $item['kegiatan']->nama }}</span>
                            <span class="text-ink-muted">{{ $item['kegiatan']->tanggal->format('d/m/Y') }}</span>
                        </div>
                        @if(!$item['sudah_ditunjuk'])
                            <a href="{{ route('kegiatan.petugas', $item['kegiatan']) }}" class="text-warning-text hover:underline">Belum ada Petugas — tunjuk sekarang</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card>
            <h2 class="mb-4 text-lg font-semibold text-ink-primary">Status Musyawaroh Bulan Ini</h2>
            <div class="space-y-3">
                @foreach($statusMusyawaroh as $item)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-ink-primary">{{ $item['label'] }}</span>
                        <x-badge :variant="$item['ada'] ? 'success' : 'neutral'">{{ $item['ada'] ? 'Sudah' : 'Belum' }}</x-badge>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card>
            <h2 class="mb-4 text-lg font-semibold text-ink-primary">Reminder: Presensi Belum Diisi Hari Ini</h2>
            @if($kelasBelumPresensi->isEmpty())
                <p class="text-sm text-ink-muted">Semua kelas sudah mengisi presensi hari ini. 🎉</p>
            @else
                <ul class="space-y-2">
                    @foreach($kelasBelumPresensi as $item)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-ink-primary">{{ $item['kelas']->nama }}</span>
                            <a href="{{ route('kegiatan.presensi', ['kegiatan' => $item['kegiatan']->id, 'kelompok' => $kelompok_id]) }}" class="text-brand-primary hover:underline">Isi sekarang</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</div>
