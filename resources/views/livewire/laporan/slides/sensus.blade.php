<table class="data-table">
    <thead><tr><th>Jenjang</th><th>Laki-laki</th><th>Perempuan</th><th>Setempat</th><th>Pendatang</th><th>Total</th></tr></thead>
    <tbody>
        @forelse(($data['per_kategori'] ?? []) as $row)
            <tr>
                <td class="font-medium text-ink-primary">{{ $row['jenjang'] }}</td>
                <td>{{ $row['laki'] }}</td>
                <td>{{ $row['perempuan'] }}</td>
                <td>{{ $row['setempat'] }}</td>
                <td>{{ $row['pendatang'] }}</td>
                <td>{{ $row['laki'] + $row['perempuan'] }}</td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty-state title="Belum ada data Sensus untuk periode ini" /></td></tr>
        @endforelse
    </tbody>
</table>
