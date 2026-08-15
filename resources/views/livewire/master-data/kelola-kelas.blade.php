<div>
    <div class="mb-4 flex items-center justify-between">
        <button type="button" wire:click="resetFilters" class="no-print text-xs text-ink-muted hover:text-brand-primary">Reset Filter</button>
        @can('kelas.manage')
            <x-button variant="primary" icon="plus" wire:click="openCreate">Tambah Kelas</x-button>
        @endcan
    </div>

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead>
                <tr><th scope="col">Nama Kelas</th><th scope="col">Kelompok</th><th scope="col">Jenjang</th><th scope="col">Status</th>@can('kelas.manage')<th scope="col" class="text-right">Aksi</th>@endcan</tr>
                <tr class="no-print">
                    <th scope="col" class="py-2 font-normal normal-case"><input type="text" wire:model.live.debounce.400ms="filterNama" aria-label="Filter Nama Kelas" placeholder="Cari nama..." class="form-control py-1 text-xs" /></th>
                    <th scope="col" class="py-2 font-normal normal-case">
                        <label for="filter-kelompok-kelas" class="sr-only">Kelompok</label>
                        <select id="filter-kelompok-kelas" wire:model.live="filterKelompok" class="form-control py-1 text-xs">
                            <option value="">Semua</option>
                            @foreach($kelompokOptions as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th scope="col"></th>
                    <th scope="col"></th>
                    @can('kelas.manage')<th scope="col" class="py-2 font-normal normal-case"></th>@endcan
                </tr>
            </thead>
            <tbody>
                @forelse($this->rombonganBelajarList as $rombel)
                    <tr wire:key="rombel-{{ $rombel->id }}">
                        <td class="font-medium text-ink-primary">{{ $rombel->nama }}</td>
                        <td>{{ $rombel->kelompok->nama }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @foreach($rombel->jenjangs as $jenjang)
                                    <x-badge variant="neutral">{{ $jenjang->label }}</x-badge>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            @can('kelas.manage')
                                <button type="button" wire:click="toggleAktif({{ $rombel->id }})">
                                    <x-badge :variant="$rombel->status_aktif ? 'success' : 'neutral'">{{ $rombel->status_aktif ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
                                </button>
                            @else
                                <x-badge :variant="$rombel->status_aktif ? 'success' : 'neutral'">{{ $rombel->status_aktif ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
                            @endcan
                        </td>
                        @can('kelas.manage')
                            <td class="text-right">
                                <button type="button" wire:click="openEdit({{ $rombel->id }})" class="mr-3 text-ink-muted hover:text-brand-primary"><x-icon name="pencil" size="16" /></button>
                                <button type="button" wire:click="hapus({{ $rombel->id }})" wire:confirm="Hapus Kelas {{ $rombel->nama }}?" class="text-ink-muted hover:text-danger-solid"><x-icon name="trash" size="16" /></button>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="Belum ada Kelas" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->rombonganBelajarList->links() }}</div>

    <x-modal wire:model="showFormModal" :title="$editingId ? 'Edit Kelas' : 'Tambah Kelas'">
        <form wire:submit="simpan" class="space-y-4">
            <x-select label="Kelompok" name="kelompok_id" wire:model="kelompok_id" :options="$kelompokOptions" placeholder="Pilih kelompok" required :error="$errors->first('kelompok_id')" />
            <x-input label="Nama Kelas" name="nama" wire:model="nama" placeholder="mis. Kelas ACR A" required :error="$errors->first('nama')" />
            <fieldset class="m-0 border-0 p-0">
                <legend class="form-label p-0">Jenjang</legend>
                <p class="mb-2 text-xs text-ink-muted">Satu Kelas bisa berisi lebih dari satu Jenjang</p>
                <div class="max-h-56 space-y-1 overflow-y-auto">
                    @foreach($jenjangOptions as $jenjang)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="jenjangIds" value="{{ $jenjang->id }}"> {{ $jenjang->label }}
                        </label>
                    @endforeach
                </div>
                @error('jenjangIds') <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p> @enderror
            </fieldset>
            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
