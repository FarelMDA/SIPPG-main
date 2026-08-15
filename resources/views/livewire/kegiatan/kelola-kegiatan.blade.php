<div>
    <x-page-header title="Daftar Kegiatan">
        <x-slot:actions>
            @can('kegiatan.manage')
                <a href="{{ route('kegiatan.tambah') }}" class="btn btn-md btn-primary">
                    <x-icon name="plus" size="16" /> Tambah Kegiatan
                </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="no-print mb-4 flex flex-wrap gap-4">
        <div class="flex gap-1">
            <button type="button" wire:click="$set('filterTingkat', '')" class="badge {{ $filterTingkat === '' ? 'badge-info' : 'badge-neutral' }}">Semua Tingkat</button>
            <button type="button" wire:click="$set('filterTingkat', 'KELOMPOK')" class="badge {{ $filterTingkat === 'KELOMPOK' ? 'badge-info' : 'badge-neutral' }}">Kelompok</button>
            <button type="button" wire:click="$set('filterTingkat', 'DESA')" class="badge {{ $filterTingkat === 'DESA' ? 'badge-info' : 'badge-neutral' }}">Desa</button>
            <button type="button" wire:click="$set('filterTingkat', 'DAERAH')" class="badge {{ $filterTingkat === 'DAERAH' ? 'badge-info' : 'badge-neutral' }}">Daerah</button>
        </div>
        <div class="flex gap-1">
            <button type="button" wire:click="$set('filterStatus', '')" class="badge {{ $filterStatus === '' ? 'badge-info' : 'badge-neutral' }}">Semua Status</button>
            <button type="button" wire:click="$set('filterStatus', 'TERJADWAL')" class="badge {{ $filterStatus === 'TERJADWAL' ? 'badge-info' : 'badge-neutral' }}">Terjadwal</button>
            <button type="button" wire:click="$set('filterStatus', 'TERLAKSANA')" class="badge {{ $filterStatus === 'TERLAKSANA' ? 'badge-info' : 'badge-neutral' }}">Terlaksana</button>
        </div>
    </div>

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead><tr><th>Nama</th><th>Tingkat</th><th>Jenis</th><th>Tanggal</th><th>Tempat</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse($this->daftar as $kegiatan)
                    <tr wire:key="kegiatan-{{ $kegiatan->id }}">
                        <td class="font-medium text-ink-primary">{{ $kegiatan->nama }}</td>
                        <td><x-status-badge :status="$kegiatan->tingkat" /></td>
                        <td>{{ $kegiatan->jenisKegiatan->nama }}</td>
                        <td>{{ $kegiatan->tanggal->format('d/m/Y') }}</td>
                        <td>{{ $kegiatan->tempat ?? '—' }}</td>
                        <td><x-status-badge :status="$kegiatan->status" /></td>
                        <td class="text-right space-x-2">
                            @can('kegiatan.manage')
                                <a href="{{ route('kegiatan.ubah', $kegiatan) }}" class="text-brand-primary hover:underline">Ubah</a>
                            @endcan
                            @if($kegiatan->tingkat !== 'KELOMPOK')
                                <a href="{{ route('kegiatan.petugas', $kegiatan) }}" class="text-brand-primary hover:underline">Petugas</a>
                            @endif
                            <a href="{{ route('kegiatan.presensi', ['kegiatan' => $kegiatan, 'kelompok' => auth('web')->user()->kelompok_id ?? $kegiatan->penyelenggara_id]) }}" class="text-brand-primary hover:underline">Presensi</a>
                            <a href="{{ route('kegiatan.rekap', $kegiatan) }}" class="text-brand-primary hover:underline">Rekap</a>
                            @can('kegiatan.manage')
                                @if($kegiatan->status === 'TERJADWAL')
                                    <button type="button" wire:click="tandaiStatus({{ $kegiatan->id }}, 'TERLAKSANA')" class="text-success-text hover:underline">Terlaksana</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state title="Belum ada Kegiatan" icon="calendar" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
