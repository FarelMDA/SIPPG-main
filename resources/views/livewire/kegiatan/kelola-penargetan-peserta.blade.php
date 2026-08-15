<div class="rounded-md border border-border p-4">
    <p class="form-label mb-2">Peserta Kegiatan</p>
    <div class="space-y-2">
        <label class="flex items-center gap-2 text-sm">
            <input type="radio" wire:model.live="targetTipe" value="SEMUA"> Semua generus dalam cakupan
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="radio" wire:model.live="targetTipe" value="JENJANG_KELAS"> Jenjang/Kelas tertentu
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="radio" wire:model.live="targetTipe" value="INDIVIDU"> Orang tertentu
        </label>
    </div>

    @if($targetTipe === 'JENJANG_KELAS')
        <div class="mt-4 border-t border-border pt-3">
            <p class="mb-1 text-xs font-medium uppercase tracking-wide text-ink-muted">Jalan pintas Jenjang</p>
            <div class="mb-3 flex flex-wrap gap-1">
                @foreach($jenjangOptions as $id => $label)
                    <button type="button" wire:click="pilihJenjang({{ $id }})" class="badge badge-neutral">{{ $label }}</button>
                @endforeach
            </div>

            <p class="mb-1 text-xs font-medium uppercase tracking-wide text-ink-muted">Kelas terpilih</p>
            <div class="flex flex-wrap gap-2">
                @foreach($kelasOptions as $kelas)
                    <label class="flex items-center gap-1.5 text-sm">
                        <input type="checkbox" wire:click="toggleKelas({{ $kelas->id }})" @checked(in_array($kelas->id, $kelasTerpilih))>
                        {{ $kelas->nama }}
                    </label>
                @endforeach
            </div>
        </div>
    @elseif($targetTipe === 'INDIVIDU')
        <div class="mt-4 border-t border-border pt-3">
            <div class="flex flex-wrap items-end gap-2">
                <x-input label="Cari Nama" name="filterNama" wire:model="filterNama" />
                <x-select label="Jenis Kelamin" name="filterJenisKelamin" wire:model="filterJenisKelamin" placeholder="Semua" :options="$jenisKelaminOptions" />
                <x-button type="button" variant="secondary" wire:click="cariIndividu">Cari</x-button>
            </div>

            @if(!empty($hasilPencarian))
                <div class="mt-3 max-h-40 overflow-y-auto rounded-md border border-border p-2">
                    @foreach($hasilPencarian as $g)
                        <label class="flex items-center gap-1.5 py-1 text-sm">
                            <input type="checkbox" wire:click="tambahIndividu({{ $g['id'] }})" @checked(in_array($g['id'], $individuTerpilih))>
                            {{ $g['nama'] }}
                        </label>
                    @endforeach
                </div>
            @endif

            <p class="mb-1 mt-3 text-xs font-medium uppercase tracking-wide text-ink-muted">Terpilih ({{ count($individuTerpilih) }})</p>
            <div class="flex max-h-32 flex-wrap gap-1 overflow-y-auto">
                @foreach($this->individuTerpilihDetail as $g)
                    <span class="badge badge-info">
                        {{ $g->nama }}
                        <button type="button" wire:click="hapusIndividu({{ $g->id }})" class="ml-1">✕</button>
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>
