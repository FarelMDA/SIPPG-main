<table class="data-table">
    <thead><tr><th>Nama</th><th>Kelas</th><th>Domisili</th></tr></thead>
    <tbody>
        @foreach($data['daftar'] as $g)
            <tr>
                <td>{{ $g['nama'] }}</td>
                <td>{{ $g['kelas'] }}</td>
                <td><x-status-badge :status="strtoupper($g['status_domisili'])" /></td>
            </tr>
        @endforeach
    </tbody>
</table>
