<div>
    <x-page-header title="Rekapitulasi Sensus" description="Rekapitulasi Data Peserta Didik">
        <x-slot:actions>
            <x-button variant="secondary" icon="printer" onclick="window.print()">Cetak / Simpan sebagai PDF</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="no-print mb-6 flex flex-wrap items-end gap-3">
        @if($kelompokOptions->count() > 1)
            <x-select label="Kelompok" name="kelompok_id" wire:model.live="kelompok_id" :options="$kelompokOptions" />
        @endif
        <x-select label="Bulan" name="bulanFilter" wire:model.live="bulanFilter" :options="$bulanOptions" />
        <x-select label="Tahun" name="tahunFilter" wire:model.live="tahunFilter" :options="$tahunOptions" />
    </div>

    <div class="no-print mb-6 flex flex-wrap items-end gap-3 border-t border-line-subtle pt-4">
        <p class="w-full text-xs font-medium uppercase tracking-wide text-ink-muted">Bandingkan dengan periode</p>
        <x-select label="Bulan Pembanding" name="bulanBanding" wire:model.live="bulanBanding" :options="$bulanOptions" />
        <x-select label="Tahun Pembanding" name="tahunBanding" wire:model.live="tahunBanding" :options="$tahunOptions" />
        <x-button variant="secondary" wire:click="bandingkanPeriode('{{ $periode }}', '{{ $periodeBanding }}')">Bandingkan</x-button>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach($kategoriUrutan as $kategori)
            @php $data = $bulanIni[$kategori] ?? ['total' => 0, 'SETEMPAT' => 0, 'PENDATANG' => 0]; @endphp
            <x-card padding="p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">{{ $kategori }}</p>
                <p class="mt-1 text-3xl font-bold text-ink-primary">{{ $data['total'] ?? 0 }}</p>
                <div class="mt-2 flex gap-1 text-xs">
                    <x-badge variant="info">{{ $data['SETEMPAT'] ?? 0 }} Setempat</x-badge>
                    <x-badge variant="neutral">{{ $data['PENDATANG'] ?? 0 }} Pendatang</x-badge>
                </div>
            </x-card>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card padding="p-5">
            <p class="text-sm text-ink-muted">Total Generus Aktif</p>
            <p class="mt-1 text-3xl font-bold text-ink-primary">{{ $jumlahGenerus }}</p>
        </x-card>
        <x-card padding="p-5">
            <p class="text-sm text-ink-muted">Total Pendidik</p>
            <p class="mt-1 text-3xl font-bold text-ink-primary">{{ $jumlahPendidik }}</p>
        </x-card>
        <x-card padding="p-5">
            <p class="text-sm text-ink-muted">Rasio Pendidik : Generus</p>
            <p class="mt-1 text-3xl font-bold text-ink-primary">1 : {{ $rasio ?? '—' }}</p>
        </x-card>
    </div>

    <x-card class="mt-6">
        <h2 class="mb-4 text-lg font-semibold text-ink-primary">Tren — {{ $periode }} vs {{ $periodeBanding }}</h2>
        <div class="space-y-4">
            @php $max = max(1, collect($kategoriUrutan)->map(fn ($k) => max($bulanIni[$k]['total'] ?? 0, $bulanLalu[$k]['total'] ?? 0))->max()); @endphp
            @foreach($kategoriUrutan as $kategori)
                @php
                    $ini = $bulanIni[$kategori]['total'] ?? 0;
                    $lalu = $bulanLalu[$kategori]['total'] ?? 0;
                @endphp
                <div>
                    <div class="mb-1 flex justify-between text-sm font-medium text-ink-primary">
                        <span>{{ $kategori }}</span>
                        <span class="text-ink-muted">{{ $lalu }} → {{ $ini }}</span>
                    </div>
                    <div class="flex h-2.5 items-center gap-0.5">
                        <div class="h-2.5 rounded-full bg-[#50A652]" style="width: {{ $max ? ($lalu / $max * 100) : 0 }}%"></div>
                    </div>
                    <div class="mt-0.5 flex h-2.5 items-center gap-0.5">
                        <div class="h-2.5 rounded-full bg-[#076B3B]" style="width: {{ $max ? ($ini / $max * 100) : 0 }}%"></div>
                    </div>
                </div>
            @endforeach
            <div class="flex gap-4 pt-2 text-xs text-ink-muted">
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#50A652]"></span> {{ $periodeBanding }}</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#076B3B]"></span> {{ $periode }}</span>
            </div>
        </div>
    </x-card>
</div>
