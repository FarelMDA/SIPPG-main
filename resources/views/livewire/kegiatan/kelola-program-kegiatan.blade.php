<div class="rounded-md border border-border p-4">
    <p class="form-label mb-2">Pengelompokan Program (Opsional)</p>

    @if(!$tampilkanFormBaru)
        <div class="flex flex-wrap items-end gap-2">
            <x-select label="Program" name="kegiatanProgramId" wire:model.live="kegiatanProgramId" placeholder="Tidak ditandai" :options="$programOptions" />
            <x-button type="button" variant="secondary" wire:click="bukaFormBaru">Buat Program Baru</x-button>
        </div>
    @else
        <div class="space-y-3">
            <x-input label="Nama Program" name="namaBaru" wire:model="namaBaru" :error="$errors->first('namaBaru')" />
            <x-select label="Tingkat Tertinggi" name="tingkatTertinggiBaru" wire:model="tingkatTertinggiBaru"
                :options="['KELOMPOK' => 'Kelompok', 'DESA' => 'Desa', 'DAERAH' => 'Daerah']" :error="$errors->first('tingkatTertinggiBaru')" />
            <div class="flex gap-2">
                <x-button type="button" variant="primary" wire:click="buatProgram">Simpan Program</x-button>
                <x-button type="button" variant="secondary" wire:click="$set('tampilkanFormBaru', false)">Batal</x-button>
            </div>
        </div>
    @endif
</div>
