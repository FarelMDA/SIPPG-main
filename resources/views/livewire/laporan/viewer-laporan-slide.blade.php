<div x-data="{ slide: 1, total: {{ count($this->slides) }} }">
    <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <h1 class="text-lg font-semibold text-ink-primary">
                {{ match($laporan->tingkat) { 'DAERAH' => 'Laporan Daerah', 'DESA' => 'Laporan Desa', default => 'Laporan Kelompok' } }} — {{ $laporan->periode }}
                <span class="text-sm font-normal text-ink-muted">v{{ $laporan->versi }}</span>
            </h1>
            <x-status-badge :status="$laporan->status" />
        </div>

        <div class="flex items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline" @click="slide = Math.max(slide - 1, 1)">
                <x-icon name="chevron-left" size="16" />
            </button>
            <span class="text-sm text-ink-muted" x-text="slide + ' / ' + total"></span>
            <button type="button" class="btn btn-sm btn-outline" @click="slide = Math.min(slide + 1, total)">
                <x-icon name="chevron-right" size="16" />
            </button>

            @if($this->bisaFinalisasi)
                <x-button wire:click="finalisasi" wire:confirm="Finalisasi laporan ini? Seluruh angka akan dibekukan dan tidak lagi mengikuti perubahan data sumber.">
                    Finalisasi
                </x-button>
            @endif

            <x-button variant="secondary" icon="printer" onclick="window.print()">Cetak / Simpan sebagai PDF</x-button>
        </div>
    </div>

    @if($laporan->status === 'REVISI_DIMINTA' && $laporan->catatan_revisi)
        <x-info-banner variant="danger" class="no-print mb-4">
            <strong>Revisi diminta:</strong> {{ $laporan->catatan_revisi }}
        </x-info-banner>
    @endif

    @if($laporan->isLive())
        <x-info-banner variant="info" class="no-print mb-4">
            Data pada laporan ini masih live dan akan berubah mengikuti data terbaru sampai Anda menekan Finalisasi.
        </x-info-banner>
    @endif

    <div class="space-y-6 print:space-y-0">
        @foreach($this->slides as $i => $s)
            <section
                x-show="slide === {{ $i + 1 }}"
                x-cloak
                class="print-page-break print:!block aspect-video overflow-auto rounded-xl border border-border bg-white p-8"
            >
                <h2 class="mb-4 text-xl font-semibold text-ink-primary">{{ $s['judul'] }}</h2>
                @include('livewire.laporan.slides.'.$s['partial'], ['data' => $s['data'], 'tingkat' => $s['tingkat'] ?? null])
            </section>
        @endforeach
    </div>

    @if($this->bolehReview)
        <div class="no-print mt-6">
            <livewire:laporan.approval-laporan :laporan-id="$laporan->id" wire:key="approval-{{ $laporan->id }}" />
        </div>
    @endif
</div>
