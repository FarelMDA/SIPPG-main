<div>
    <x-page-header title="Kelola Jenis Kegiatan">
        <x-slot:actions>
            <x-button variant="primary" icon="plus" wire:click="openCreate">Tambah Jenis Kegiatan</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead><tr><th>Nama</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse($this->daftar as $jenisKegiatan)
                    <tr wire:key="jenis-kegiatan-{{ $jenisKegiatan->id }}">
                        <td class="font-medium text-ink-primary">{{ $jenisKegiatan->nama }}</td>
                        <td class="text-right space-x-2">
                            <button type="button" wire:click="openEdit({{ $jenisKegiatan->id }})" class="text-brand-primary hover:underline">Ubah</button>
                            <button type="button"
                                wire:click="hapus({{ $jenisKegiatan->id }})"
                                wire:confirm="Hapus Jenis Kegiatan ini?"
                                class="text-danger-text hover:underline">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2"><x-empty-state title="Belum ada Jenis Kegiatan tercatat" icon="list-checks" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal wire:model="showFormModal" title="{{ $editingId ? 'Ubah' : 'Tambah' }} Jenis Kegiatan">
        <form wire:submit="simpan" class="space-y-4">
            <x-input label="Nama" name="nama" wire:model="nama" required :error="$errors->first('nama')" />

            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
