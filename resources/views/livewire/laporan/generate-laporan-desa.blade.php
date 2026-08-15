<div>
    <x-page-header title="Laporan Bulanan Desa" description="Agregasi otomatis dari laporan Kelompok yang sudah final di Desa ini" />

    <div class="card mb-6 flex flex-wrap items-end gap-3 p-4">
        <div>
            <label class="form-label" for="periode">Periode</label>
            <input type="month" id="periode" wire:model="periode" class="form-control" />
        </div>
        <x-button wire:click="generate" icon="file-text">Generate Laporan</x-button>
    </div>

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
                    <tr><td colspan="5"><x-empty-state title="Belum ada Laporan Bulanan Desa" icon="file-text" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <a href="{{ route('laporan.telusur') }}" class="text-sm text-brand-primary hover:underline">
            Lihat laporan Kelompok individual di desa ini →
        </a>
    </div>
</div>
