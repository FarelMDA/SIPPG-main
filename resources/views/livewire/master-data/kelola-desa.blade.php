<div>
    <div class="mb-4 flex items-center justify-between">
        <button type="button" wire:click="resetFilters" class="no-print text-xs text-ink-muted hover:text-brand-primary">Reset Filter</button>
        @can('struktur-organisasi.manage')
            <x-button variant="primary" icon="plus" wire:click="openCreate">Tambah Desa</x-button>
        @endcan
    </div>

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead>
                <tr><th scope="col">Nama Desa</th>@can('struktur-organisasi.manage')<th scope="col" class="text-right">Aksi</th>@endcan</tr>
                <tr class="no-print">
                    <th scope="col" class="py-2 font-normal normal-case"><input type="text" wire:model.live.debounce.400ms="filterNama" aria-label="Filter Nama Desa" placeholder="Cari nama..." class="form-control py-1 text-xs" /></th>
                    @can('struktur-organisasi.manage')<th scope="col" class="py-2 font-normal normal-case"></th>@endcan
                </tr>
            </thead>
            <tbody>
                @forelse($this->desaList as $desa)
                    <tr wire:key="desa-{{ $desa->id }}">
                        <td class="font-medium text-ink-primary">{{ $desa->nama }}</td>
                        @can('struktur-organisasi.manage')
                            <td class="text-right">
                                <button type="button" wire:click="openEdit({{ $desa->id }})" class="mr-3 text-ink-muted hover:text-brand-primary"><x-icon name="pencil" size="16" /></button>
                                <button type="button" wire:click="hapus({{ $desa->id }})" wire:confirm="Hapus desa {{ $desa->nama }}?" class="text-ink-muted hover:text-danger-solid"><x-icon name="trash" size="16" /></button>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="2"><x-empty-state title="Belum ada Desa" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->desaList->links() }}</div>

    <x-modal wire:model="showFormModal" :title="$editingId ? 'Edit Desa' : 'Tambah Desa'" max-width="sm">
        <form wire:submit="simpan" class="space-y-4">
            <x-input label="Nama Desa" name="nama" wire:model="nama" required :error="$errors->first('nama')" />
            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
