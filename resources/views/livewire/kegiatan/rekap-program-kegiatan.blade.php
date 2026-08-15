<div>
    <x-page-header title="Rekap Program Kegiatan">
        <x-slot:actions>
            <x-button variant="secondary" icon="printer" onclick="window.print()">Cetak / Simpan sebagai PDF</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="no-print mb-6 flex flex-wrap items-end gap-3">
        <x-select label="Program" name="kegiatanProgramId" wire:model.live="kegiatanProgramId" placeholder="Pilih Program" :options="$programOptions" />
        <x-input label="Periode" name="periode" type="month" wire:model.live="periode" />
    </div>

    @if(!$kegiatanProgramId)
        <x-empty-state title="Pilih Program untuk melihat rekap" icon="layers" />
    @else
        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <x-card padding="p-5">
                <p class="text-sm text-ink-muted">Total Kehadiran Gabungan</p>
                <p class="mt-1 text-3xl font-bold text-ink-primary">{{ $totalHadir }} / {{ $totalPeserta }}</p>
            </x-card>
        </div>

        <x-card class="overflow-x-auto p-0">
            @forelse($rekapPerPenyelenggara as $row)
                <div class="border-b border-border p-4 last:border-0">
                    <p class="mb-2 font-semibold text-ink-primary">
                        <x-status-badge :status="$row['tingkat']" /> {{ $row['penyelenggara'] }}
                        — {{ $row['persentase'] }}% kehadiran
                    </p>
                    <table class="data-table">
                        <thead><tr><th>Nama Kegiatan</th><th>Tanggal</th><th>Tingkat</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpha</th></tr></thead>
                        <tbody>
                            @foreach($row['kegiatan'] as $k)
                                <tr wire:key="rekap-program-{{ $k->id }}">
                                    <td>{{ $k->nama }}</td>
                                    <td>{{ $k->tanggal->format('d/m/Y') }}</td>
                                    <td><x-status-badge :status="$k->tingkat" /></td>
                                    <td>{{ $k->peserta->where('status_presensi', 'HADIR')->count() }}</td>
                                    <td>{{ $k->peserta->where('status_presensi', 'IZIN')->count() }}</td>
                                    <td>{{ $k->peserta->where('status_presensi', 'SAKIT')->count() }}</td>
                                    <td>{{ $k->peserta->where('status_presensi', 'ALPHA')->count() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <x-empty-state title="Belum ada Kegiatan tercatat untuk Program & periode ini" />
            @endforelse
        </x-card>
    @endif
</div>
