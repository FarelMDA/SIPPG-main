<div>
    <x-page-header title="Jadwal Kegiatan Berulang">
        <x-slot:actions>
            <a href="{{ route('kegiatan.jadwal.tambah') }}" class="btn btn-md btn-primary">
                <x-icon name="plus" size="16" /> Tambah Jadwal
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="no-print mb-4 flex gap-1">
        <button type="button" wire:click="$set('filterStatus', '')" class="badge {{ $filterStatus === '' ? 'badge-info' : 'badge-neutral' }}">Semua Status</button>
        <button type="button" wire:click="$set('filterStatus', 'AKTIF')" class="badge {{ $filterStatus === 'AKTIF' ? 'badge-info' : 'badge-neutral' }}">Aktif</button>
        <button type="button" wire:click="$set('filterStatus', 'NONAKTIF')" class="badge {{ $filterStatus === 'NONAKTIF' ? 'badge-info' : 'badge-neutral' }}">Nonaktif</button>
    </div>

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead><tr><th>Nama</th><th>Frekuensi</th><th>Rentang Tanggal</th><th>Status</th><th>Jumlah Kejadian</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse($this->daftar as $jadwal)
                    <tr wire:key="jadwal-{{ $jadwal->id }}">
                        <td class="font-medium text-ink-primary">{{ $jadwal->nama }}</td>
                        <td><x-status-badge :status="$jadwal->frekuensi_tipe" /></td>
                        <td>{{ $jadwal->tanggal_mulai->format('d/m/Y') }} – {{ $jadwal->tanggal_selesai->format('d/m/Y') }}</td>
                        <td><x-status-badge :status="$jadwal->status" /></td>
                        <td>{{ $jadwal->kegiatan_count }}</td>
                        <td class="text-right space-x-2">
                            <a href="{{ route('kegiatan.jadwal.ubah', $jadwal) }}" class="text-brand-primary hover:underline">Ubah</a>
                            @if($jadwal->status === 'AKTIF')
                                <button type="button" wire:click="nonaktifkan({{ $jadwal->id }})" wire:confirm="Nonaktifkan Jadwal ini? Kejadian yang sudah dibuat tidak akan dihapus." class="text-danger-text hover:underline">Nonaktifkan</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state title="Belum ada Jadwal Kegiatan Berulang" icon="repeat" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
