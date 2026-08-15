<div>
    <x-page-header title="Rekap KBM Reguler Lintas Kelompok" />

    <div class="mb-4 flex flex-wrap gap-4">
        <x-select label="Jenjang" name="filterJenjang" wire:model.live="filterJenjang" :options="$jenjangOptions" />
        @can('kegiatan.view')
            @if(auth('web')->user()->hasRole('admin-daerah'))
                <x-select label="Desa" name="filterDesaId" wire:model.live="filterDesaId" :options="$desaOptions" placeholder="Semua Desa" />
            @endif
        @endcan
        <x-input label="Bulan" name="bulan" type="month" wire:model.live="bulan" />
    </div>

    @forelse($rekapPerKelompok as $namaKelompok => $rows)
        <x-card class="mb-4">
            <p class="mb-2 font-semibold text-ink-primary">{{ $namaKelompok }}</p>
            <table class="data-table">
                <thead><tr><th>Kelas</th><th>Jumlah Jadwal</th><th>Terlaksana</th><th>% Kehadiran</th></tr></thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row['kelas']->nama }}</td>
                            <td>{{ $row['total_jadwal'] }}</td>
                            <td>{{ $row['terlaksana'] }}</td>
                            <td>{{ $row['persentase_kehadiran'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>
    @empty
        <x-empty-state title="Belum ada Kegiatan KBM tercatat" description="Pastikan Jenjang sudah dipilih dan ada Jadwal Kegiatan Berulang bertipe Rutin dari Kurikulum yang sudah generate." icon="calendar" />
    @endforelse
</div>
