<div>
    <div class="mb-4 flex items-center justify-end">
        <x-button variant="primary" icon="plus" wire:click="openCreate">Tambah {{ $judul }}</x-button>
    </div>

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Kode</th>
                    <th scope="col">Label</th>
                    <th scope="col">Urutan</th>
                    @if($withKategoriUsia)<th scope="col">Kategori Usia</th>@endif
                    <th scope="col" class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->daftar as $baris)
                    <tr wire:key="referensi-{{ $baris->id }}">
                        <td class="font-medium text-ink-primary">{{ $baris->kode }}</td>
                        <td>{{ $baris->label }}</td>
                        <td>{{ $baris->urutan }}</td>
                        @if($withKategoriUsia)<td>{{ $baris->kategori_usia }}</td>@endif
                        <td class="text-right">
                            <button type="button" wire:click="openEdit({{ $baris->id }})" class="text-ink-muted hover:text-brand-primary"><x-icon name="pencil" size="16" /></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $withKategoriUsia ? 5 : 4 }}"><x-empty-state title="Belum ada {{ $judul }}" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal wire:model="showFormModal" :title="$editingId ? 'Edit '.$judul : 'Tambah '.$judul" max-width="sm">
        <form wire:submit="simpan" class="space-y-4">
            <x-input label="Kode" name="kode" wire:model="kode" required :error="$errors->first('kode')" description="Huruf/angka/underscore, tanpa spasi" />
            <x-input label="Label" name="label" wire:model="label" required :error="$errors->first('label')" />
            <x-input label="Urutan" name="urutan" type="number" wire:model="urutan" required :error="$errors->first('urutan')" />
            @if($withKategoriUsia)
                <x-input label="Kategori Usia" name="kategori_usia" wire:model="kategori_usia" required :error="$errors->first('kategori_usia')" />
            @endif
            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
