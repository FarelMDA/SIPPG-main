<div>
    <x-page-header :title="$kegiatan ? 'Ubah Kegiatan' : 'Tambah Kegiatan'" />

    <x-card class="max-w-2xl">
        <form wire:submit="simpan" class="space-y-4">
            <div class="rounded-md bg-surface-subtle px-3 py-2 text-sm">
                Tingkat: <x-status-badge :status="$tingkatSaya" />
            </div>
            <x-input label="Nama Kegiatan" name="nama" wire:model="nama" required :error="$errors->first('nama')" />
            <x-input label="Deskripsi" name="deskripsi" wire:model="deskripsi" />
            <x-select label="Jenis" name="jenisKegiatanId" wire:model="jenisKegiatanId" :options="$jenisOptions" placeholder="Pilih Jenis" required :error="$errors->first('jenisKegiatanId')" />
            <x-input label="Tanggal" name="tanggal" type="date" wire:model.live="tanggal" required :error="$errors->first('tanggal')" />
            @if($peringatanHariLibur)
                <x-info-banner variant="warning">{{ $peringatanHariLibur }}</x-info-banner>
            @endif
            <x-input label="Tempat" name="tempat" wire:model="tempat" :error="$errors->first('tempat')" />

            <livewire:kegiatan.kelola-penargetan-peserta
                :tingkat="$tingkatSaya"
                :penyelenggara-id="$penyelenggaraIdSaya"
                :target-tipe="$targetTipe"
                :kelas-awal="$kelasTerpilih"
                :individu-awal="$individuTerpilih"
                wire:key="kegiatan-penargetan-{{ $kegiatan?->id ?? 'baru' }}"
            />

            <livewire:kegiatan.kelola-program-kegiatan
                :kegiatan-program-id="$kegiatanProgramId"
                wire:key="kegiatan-program-{{ $kegiatan?->id ?? 'baru' }}"
            />

            @if($tingkatSaya !== 'KELOMPOK')
                <x-info-banner>Setelah Kegiatan disimpan, tiap Kelompok peserta perlu menunjuk Petugas Presensi sendiri — lihat halaman Kelola Petugas Presensi.</x-info-banner>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('kegiatan.index') }}" class="btn btn-md btn-secondary">Batal</a>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-card>
</div>
