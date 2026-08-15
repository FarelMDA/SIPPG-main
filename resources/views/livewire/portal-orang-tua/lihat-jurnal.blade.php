<div>
    <h1 class="mb-4 text-xl font-semibold text-ink-primary">Jurnal Materi Anak</h1>

    @include('livewire.portal-orang-tua.partials.pemilih-anak')

    <div class="mb-4">
        <input type="month" wire:model.live="bulan" class="form-control w-auto" />
    </div>

    <div class="space-y-3">
        @forelse($jurnal as $row)
            <x-card padding="p-4">
                <div class="mb-1 flex items-center justify-between">
                    <span class="font-medium text-ink-primary">{{ $row->tanggal->format('d/m/Y') }}</span>
                    <x-status-badge :status="$row->realisasi_status" />
                </div>
                @if(!empty($row->materi))
                    <ul class="mb-1 list-inside list-disc text-sm text-ink-primary">
                        @foreach($row->materi as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif
                <p class="text-sm text-ink-secondary">{{ $row->realisasi_catatan ?? 'Tidak ada catatan.' }}</p>
            </x-card>
        @empty
            <x-empty-state title="Belum ada data jurnal" />
        @endforelse
    </div>
</div>
