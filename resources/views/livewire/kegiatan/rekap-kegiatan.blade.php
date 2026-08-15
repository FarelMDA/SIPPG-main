<div>
    <x-page-header title="Rekap Kegiatan" :description="$kegiatan->nama.' — '.$kegiatan->tanggal->format('d/m/Y')">
        <x-slot:actions>
            <x-button variant="secondary" icon="printer" onclick="window.print()">Cetak / Simpan sebagai PDF</x-button>
        </x-slot:actions>
    </x-page-header>

    @if($kegiatan->tingkat !== 'KELOMPOK')
        <x-card class="mb-6 print-page-break">
            <h2 class="mb-3 text-lg font-semibold text-ink-primary">Rekap per Kelompok</h2>
            <table class="data-table">
                <thead><tr><th>Kelompok</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpha</th><th>% Kehadiran</th></tr></thead>
                <tbody>
                    @forelse($rekapPerKelompok as $row)
                        <tr>
                            <td class="font-medium text-ink-primary">{{ $row['kelompok']->nama }}</td>
                            <td>{{ $row['hadir'] }}</td>
                            <td>{{ $row['izin'] }}</td>
                            <td>{{ $row['sakit'] }}</td>
                            <td>{{ $row['alpha'] }}</td>
                            <td>{{ $row['persentase'] }}%</td>
                        </tr>
                        @foreach($row['per_kelas'] as $kelasRow)
                            <tr class="text-sm text-ink-muted">
                                <td class="pl-6">↳ {{ $kelasRow['kelas']->nama }}</td>
                                <td>{{ $kelasRow['hadir'] }}</td>
                                <td>{{ $kelasRow['izin'] }}</td>
                                <td>{{ $kelasRow['sakit'] }}</td>
                                <td>{{ $kelasRow['alpha'] }}</td>
                                <td>{{ $kelasRow['persentase'] }}%</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="6"><x-empty-state title="Belum ada presensi tercatat" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    @endif

    <x-card class="mb-6">
        <h2 class="mb-3 text-lg font-semibold text-ink-primary">Rekap Generus</h2>
        @forelse($pesertaPerKelompok as $namaKelompok => $rows)
            <p class="mb-2 mt-4 text-sm font-semibold text-ink-secondary first:mt-0">{{ $namaKelompok }}</p>
            <table class="data-table mb-2">
                <thead><tr><th>Nama Generus</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row->generus->nama }}</td>
                            <td><x-status-badge :status="$row->status_presensi" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <x-empty-state title="Belum ada peserta tercatat" />
        @endforelse
    </x-card>

    <x-card class="print-page-break">
        <h2 class="mb-3 text-lg font-semibold text-ink-primary">Status Program Monitoring per Kelompok</h2>
        @forelse($rekapPerKelompok as $kelompokId => $row)
            <p class="mb-2 mt-4 text-sm font-semibold text-ink-secondary first:mt-0">{{ $row['kelompok']->nama }}</p>
            @php $programs = $programMonitoringPerKelompok->get($kelompokId, collect()); @endphp
            @if($programs->isEmpty())
                <p class="mb-2 text-sm text-ink-muted">Belum ada Program Monitoring tercatat.</p>
            @else
                <table class="data-table mb-2">
                    <thead><tr><th>Program</th><th>Tenggat</th><th>Status</th><th>Item Selesai</th></tr></thead>
                    <tbody>
                        @foreach($programs as $program)
                            <tr>
                                <td>{{ $program->nama_program }}</td>
                                <td>{{ $program->tenggat?->format('d/m/Y') ?? '—' }}</td>
                                <td><x-status-badge :status="$program->status" /></td>
                                <td>{{ $program->items->where('status_item', 'SELESAI')->count() }} / {{ $program->items->count() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @empty
            <x-empty-state title="Belum ada Kelompok peserta" />
        @endforelse
    </x-card>
</div>
