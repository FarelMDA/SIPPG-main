<div>
    <h1 class="mb-4 text-xl font-semibold text-ink-primary">Presensi Anak</h1>

    @include('livewire.portal-orang-tua.partials.pemilih-anak')

    <div class="mb-4">
        <input type="month" wire:model.live="bulan" class="form-control w-auto" />
    </div>

    <div class="card overflow-hidden">
        <table class="data-table">
            <thead><tr><th>Tanggal</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($presensi as $row)
                    <tr>
                        <td>{{ $row->kegiatan->tanggal->format('d/m/Y') }}</td>
                        <td><x-status-badge :status="$row->status_presensi" /></td>
                    </tr>
                @empty
                    <tr><td colspan="2"><x-empty-state title="Belum ada data presensi" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
