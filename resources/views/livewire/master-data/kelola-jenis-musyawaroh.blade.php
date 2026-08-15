<div>
    <x-page-header title="Kelola Jenis Musyawaroh">
        <x-slot:actions>
            <x-button variant="primary" icon="plus" wire:click="openCreate">Tambah Jenis Musyawaroh</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead><tr><th>Tingkat</th><th>Nama</th><th>Urutan</th><th>Perlu Jumlah Hadir</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse($this->daftar as $jenisMusyawaroh)
                    <tr wire:key="jenis-musyawaroh-{{ $jenisMusyawaroh->id }}">
                        <td><x-badge variant="neutral">{{ ucfirst(strtolower($jenisMusyawaroh->tingkat)) }}</x-badge></td>
                        <td class="font-medium text-ink-primary">{{ $jenisMusyawaroh->nama }}</td>
                        <td>{{ $jenisMusyawaroh->urutan }}</td>
                        <td>{{ $jenisMusyawaroh->perlu_jumlah_hadir ? 'Ya' : 'Tidak' }}</td>
                        <td class="text-right space-x-2">
                            <button type="button" wire:click="openEdit({{ $jenisMusyawaroh->id }})" class="text-brand-primary hover:underline">Ubah</button>
                            <button type="button"
                                wire:click="hapus({{ $jenisMusyawaroh->id }})"
                                wire:confirm="Hapus Jenis Musyawaroh ini?"
                                class="text-danger-text hover:underline">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="Belum ada Jenis Musyawaroh tercatat" icon="users" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal wire:model="showFormModal" title="{{ $editingId ? 'Ubah' : 'Tambah' }} Jenis Musyawaroh">
        <form wire:submit="simpan" class="space-y-4">
            <x-select label="Tingkat" name="tingkat" wire:model="tingkat" :options="['KELOMPOK' => 'Kelompok', 'DESA' => 'Desa', 'DAERAH' => 'Daerah']" required :error="$errors->first('tingkat')" />
            <x-input label="Nama" name="nama" wire:model="nama" required :error="$errors->first('nama')" />
            <x-input label="Urutan" name="urutan" type="number" wire:model="urutan" required :error="$errors->first('urutan')" />

            <div class="flex items-center gap-2">
                <input type="checkbox" id="perlu_jumlah_hadir" wire:model="perlu_jumlah_hadir" class="rounded border-border" />
                <label for="perlu_jumlah_hadir" class="text-sm text-ink-primary">Wajib isi Jumlah Hadir saat mencatat musyawaroh</label>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
