@php
    // Tingkat DESA: [{kelompok, items}]; Tingkat DAERAH: [{desa, items}] (SRS-Fase-2 §3.4/§3.5).
    $tingkat ??= 'KELOMPOK';
    $grupList = in_array($tingkat, ['DESA', 'DAERAH'], true) ? $data : [['label' => null, 'items' => $data]];
@endphp
@foreach($grupList as $grup)
    @if($tingkat !== 'KELOMPOK')
        <p class="mb-2 mt-4 text-sm font-semibold text-ink-secondary first:mt-0">{{ $grup['kelompok'] ?? $grup['desa'] }}</p>
    @endif
    <table class="data-table mb-4">
        <thead><tr><th>Program</th><th>Belum</th><th>Proses</th><th>Selesai</th></tr></thead>
        <tbody>
            @forelse($grup['items'] as $row)
                <tr>
                    <td class="font-medium text-ink-primary">{{ $row['nama_program'] }}</td>
                    <td>{{ $row['ringkasan_status']['belum'] }}</td>
                    <td>{{ $row['ringkasan_status']['proses'] }}</td>
                    <td>{{ $row['ringkasan_status']['selesai'] }}</td>
                </tr>
            @empty
                <tr><td colspan="4"><x-empty-state title="Belum ada Program Monitoring tercatat" /></td></tr>
            @endforelse
        </tbody>
    </table>
@endforeach
