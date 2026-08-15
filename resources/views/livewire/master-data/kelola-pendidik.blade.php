<div>
    <x-page-header title="Data Pendidik" description="Kelola data Pendidik beserta jenisnya">
        <x-slot:actions>
            <x-button variant="primary" icon="plus" wire:click="openCreate">Tambah Pendidik</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="no-print mb-2 flex justify-end">
        <button type="button" wire:click="resetFilters" class="text-xs text-ink-muted hover:text-brand-primary">Reset Filter</button>
    </div>

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead>
                <tr><th scope="col">Nama</th><th scope="col">Status</th><th scope="col">Kelompok</th><th scope="col">Desa</th><th scope="col">Kelas yang Diampu</th><th scope="col" class="text-right">Aksi</th></tr>
                <tr class="no-print">
                    <th scope="col" class="py-2 font-normal normal-case"><input type="text" wire:model.live.debounce.400ms="filterNama" aria-label="Filter Nama" placeholder="Cari nama..." class="form-control py-1 text-xs" /></th>
                    <th scope="col" class="py-2 font-normal normal-case">
                        <select wire:model.live="filterJenis" aria-label="Filter Status" class="form-control py-1 text-xs">
                            <option value="">Semua</option>
                            @foreach($jenisPendidikOptions as $kode => $label)
                                <option value="{{ $kode }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th scope="col" class="py-2 font-normal normal-case">
                        <select wire:model.live="filterKelompok" aria-label="Filter Kelompok" class="form-control py-1 text-xs">
                            <option value="">Semua</option>
                            @foreach($filterKelompokOptions as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th scope="col" class="py-2 font-normal normal-case">
                        <select wire:model.live="filterDesa" aria-label="Filter Desa" class="form-control py-1 text-xs">
                            <option value="">Semua</option>
                            @foreach($filterDesaOptions as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th scope="col" class="py-2 font-normal normal-case">
                        <select wire:model.live="filterKelas" aria-label="Filter Kelas yang Diampu" class="form-control py-1 text-xs">
                            <option value="">Semua</option>
                            @foreach($filterKelasOptions as $kelas)
                                <option value="{{ $kelas->id }}">{{ $kelas->nama }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th scope="col" class="py-2 font-normal normal-case"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->pendidikList as $pendidik)
                    <tr wire:key="pendidik-{{ $pendidik->id }}">
                        <td class="font-medium text-ink-primary">{{ $pendidik->nama }}</td>
                        <td><x-badge variant="neutral">{{ $jenisPendidikOptions[$pendidik->jenis] ?? $pendidik->jenis }}</x-badge></td>
                        <td>{{ $pendidik->kelompok->nama ?? '—' }}</td>
                        <td>{{ $pendidik->kelompok->desa->nama ?? '—' }}</td>
                        <td class="space-x-1">
                            @forelse($pendidik->kelas as $kelas)
                                <x-badge variant="neutral">{{ $kelas->nama }}</x-badge>
                            @empty
                                <span class="text-ink-muted">—</span>
                            @endforelse
                        </td>
                        <td class="text-right">
                            <button type="button" wire:click="openEdit({{ $pendidik->id }})" class="text-ink-muted hover:text-brand-primary"><x-icon name="pencil" size="16" /></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state title="Belum ada Pendidik" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->pendidikList->links() }}</div>

    <x-modal wire:model="showFormModal" :title="$editingId ? 'Edit Pendidik' : 'Tambah Pendidik'">
        <form wire:submit="simpan" class="space-y-4">
            <x-input label="Nama" name="nama" wire:model="nama" required :error="$errors->first('nama')" />

            <div>
                <span class="form-label">Jenis <span class="text-danger-solid">*</span></span>
                <div class="flex gap-4">
                    @foreach($jenisPendidikOptions as $kode => $label)
                        <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model="jenis" value="{{ $kode }}"> {{ $label }}</label>
                    @endforeach
                </div>
            </div>

            @if($bisaPilihDesa)
                <x-select label="Desa" name="desa_id" wire:model.live="desa_id" :options="$desaOptions" placeholder="Pilih desa" required :error="$errors->first('desa_id')" />
            @endif

            @if($bisaPilihKelompok)
                <x-select label="Kelompok" name="kelompok_id" wire:model.live="kelompok_id" :options="$kelompokOptions" placeholder="Pilih kelompok" required :error="$errors->first('kelompok_id')" />
            @endif

            <div>
                <span class="form-label">Kelas yang Diampu</span>
                <div class="grid grid-cols-2 gap-2">
                    @if($bisaPilihKelompok && ! $kelompok_id)
                        <p class="text-sm text-ink-muted">Pilih Kelompok dahulu untuk melihat daftar kelas.</p>
                    @endif
                    @foreach($kelasOptions as $kelas)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="kelas_ids" value="{{ $kelas->id }}"> {{ $kelas->nama }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
