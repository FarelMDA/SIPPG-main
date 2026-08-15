<table class="data-table">
    <thead><tr><th>Nama</th><th>Jabatan</th></tr></thead>
    <tbody>
        @forelse($data as $row)
            <tr>
                <td>{{ $row['nama'] }}</td>
                <td>{{ $row['jabatan'] }}</td>
            </tr>
        @empty
            <tr><td colspan="2"><x-empty-state title="Belum ada Pengurus tercatat" /></td></tr>
        @endforelse
    </tbody>
</table>
