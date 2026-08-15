<div>
    <x-page-header title="Antrian Approval Laporan" description="Laporan yang sudah difinalisasi dan menunggu review Anda" />

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead><tr><th>Kelompok/Desa</th><th>Periode</th><th>Difinalisasi Pada</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse($this->antrian as $laporan)
                    <tr wire:key="antrian-{{ $laporan->id }}">
                        <td class="font-medium text-ink-primary">{{ $laporan->tingkat === 'DESA' ? $laporan->desa->nama : $laporan->kelompok->nama }}</td>
                        <td>{{ $laporan->periode }} (v{{ $laporan->versi }})</td>
                        <td>{{ $laporan->difinalisasi_pada?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-right">
                            <a href="{{ route('laporan.viewer', $laporan) }}" class="text-brand-primary hover:underline">Tinjau</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty-state title="Tidak ada laporan menunggu review" icon="clipboard-check" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
