<div>
    <x-page-header title="Telusur Laporan Individual Berjenjang" description="Lihat riwayat laporan tiap entitas di bawah scope Anda — seluruh status & versi, bukan hanya yang menunggu review" />

    @if(auth()->user()->hasRole('admin-daerah'))
        <div class="mb-4 flex gap-2">
            <button type="button" wire:click="$set('tingkat', 'DESA')" class="btn btn-sm {{ $tingkat === 'DESA' ? 'btn-primary' : 'btn-outline' }}">Desa</button>
            <button type="button" wire:click="$set('tingkat', 'KELOMPOK')" class="btn btn-sm {{ $tingkat === 'KELOMPOK' ? 'btn-primary' : 'btn-outline' }}">Kelompok</button>
        </div>
    @endif

    <div class="card mb-6 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-64">
            <label class="form-label" for="entitasId">{{ $tingkat === 'DESA' ? 'Desa' : 'Kelompok' }}</label>
            <select id="entitasId" class="form-control" wire:model.live="entitasId">
                <option value="">— Pilih {{ $tingkat === 'DESA' ? 'Desa' : 'Kelompok' }} —</option>
                @foreach($this->entitasList as $entitas)
                    <option value="{{ $entitas->id }}">{{ $entitas->nama }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if($entitasId)
        <div class="card overflow-x-auto p-0">
            <table class="data-table">
                <thead><tr><th>Periode</th><th>Versi</th><th>Status</th><th>Difinalisasi Pada</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse($this->daftar as $periode => $versiList)
                        @php $terbaru = $versiList->first(); @endphp
                        <tr wire:key="periode-{{ $periode }}" class="cursor-pointer hover:bg-surface-page" onclick="window.location='{{ route('laporan.viewer', $terbaru) }}'">
                            <td class="font-medium text-ink-primary">{{ $periode }}</td>
                            <td>v{{ $terbaru->versi }}</td>
                            <td><x-status-badge :status="$terbaru->status" /></td>
                            <td>{{ $terbaru->difinalisasi_pada?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-right" onclick="event.stopPropagation()">
                                @if($versiList->count() > 1)
                                    <div x-data="{ open: false }" class="inline-block text-left">
                                        <button type="button" @click="open = !open" class="text-brand-primary hover:underline">Lihat versi sebelumnya</button>
                                        <div x-show="open" x-cloak class="mt-2 space-y-1 text-sm text-ink-muted">
                                            @foreach($versiList->skip(1) as $lama)
                                                <div>
                                                    <a href="{{ route('laporan.viewer', $lama) }}" class="hover:underline">v{{ $lama->versi }}</a>
                                                    — <x-status-badge :status="$lama->status" />
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state title="Belum ada laporan untuk entitas ini" icon="file-text" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <x-empty-state title="Pilih {{ $tingkat === 'DESA' ? 'Desa' : 'Kelompok' }} untuk melihat riwayat laporannya" icon="search" />
    @endif
</div>
