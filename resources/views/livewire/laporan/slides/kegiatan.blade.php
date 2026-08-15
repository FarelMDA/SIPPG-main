@php
    // Tingkat DESA: $data berbentuk [{kelompok, items}] (SRS-Fase-2 §3.4).
    // Tingkat DAERAH: $data berbentuk [{desa, items}] (SRS-Fase-2 §3.5). Kelompok tetap flat.
    $tingkat ??= 'KELOMPOK';
    $grupList = in_array($tingkat, ['DESA', 'DAERAH'], true) ? $data : [['label' => null, 'items' => $data]];
@endphp
@foreach($grupList as $grup)
    @if($tingkat !== 'KELOMPOK')
        <p class="mb-2 mt-4 text-sm font-semibold text-ink-secondary first:mt-0">{{ $grup['kelompok'] ?? $grup['desa'] }}</p>
    @endif
    <table class="data-table mb-4">
        <thead><tr><th>Nama Kegiatan</th><th>Tanggal</th><th>Status</th><th>% Kehadiran</th></tr></thead>
        <tbody>
            @forelse($grup['items'] as $row)
                <tr>
                    <td>{{ $row['nama'] }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($row['tanggal'])->format('d/m/Y') }}</td>
                    <td><x-status-badge :status="$row['status']" /></td>
                    <td>{{ $row['persentase_kehadiran'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="4"><x-empty-state title="Belum ada Kegiatan Tambahan periode ini" /></td></tr>
            @endforelse
        </tbody>
    </table>
@endforeach
