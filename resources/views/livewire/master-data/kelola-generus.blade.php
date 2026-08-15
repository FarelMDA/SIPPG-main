<div>
    <x-page-header title="Data Generus" description="Sensus, presensi, dan pembinaan mengacu ke data ini">
        @if($bisaKelola)
            <x-slot:actions>
                <x-button variant="primary" icon="plus" wire:click="openCreate">Tambah Generus</x-button>
            </x-slot:actions>
        @endif
    </x-page-header>

    <div class="no-print mb-2 flex justify-end">
        <button type="button" wire:click="resetFilters" class="text-xs text-ink-muted hover:text-brand-primary">Reset Filter</button>
    </div>

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Nama</th>
                    <th scope="col">Jenjang</th>
                    <th scope="col">Kelompok</th>
                    <th scope="col">Status Domisili</th>
                    <th scope="col">Nama Orang Tua</th>
                    <th scope="col">Nomor HP Orang Tua</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-right">Aksi</th>
                </tr>
                <tr class="no-print">
                    <th scope="col" class="py-2 font-normal normal-case"><input type="text" wire:model.live.debounce.400ms="filterNama" aria-label="Filter Nama" placeholder="Cari nama..." class="form-control py-1 text-xs" /></th>
                    <th scope="col" class="py-2 font-normal normal-case">
                        <select wire:model.live="filterKelas" aria-label="Filter Jenjang" class="form-control py-1 text-xs">
                            <option value="">Semua</option>
                            @foreach($filterKelasOptions as $kelas)
                                <option value="{{ $kelas->id }}">{{ $kelas->nama }}</option>
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
                        <select wire:model.live="filterStatusDomisili" aria-label="Filter Status Domisili" class="form-control py-1 text-xs">
                            <option value="">Semua</option>
                            @foreach($statusDomisiliOptions as $kode => $label)
                                <option value="{{ $kode }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th scope="col" class="py-2 font-normal normal-case"><input type="text" wire:model.live.debounce.400ms="filterNamaOrangTua" aria-label="Filter Nama Orang Tua" placeholder="Cari nama..." class="form-control py-1 text-xs" /></th>
                    <th scope="col" class="py-2 font-normal normal-case"></th>
                    <th scope="col" class="py-2 font-normal normal-case">
                        <select wire:model.live="filterStatusAktif" aria-label="Filter Status" class="form-control py-1 text-xs">
                            <option value="">Semua</option>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </th>
                    <th scope="col" class="py-2 font-normal normal-case"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->generusList as $generus)
                    <tr wire:key="generus-{{ $generus->id }}">
                        <td class="font-medium text-ink-primary">{{ $generus->nama }}</td>
                        <td>{{ $generus->jenjang->label }}</td>
                        <td>{{ $generus->kelas->kelompok->nama ?? '—' }}</td>
                        <td><x-status-badge :status="$generus->status_domisili" /></td>
                        <td>{{ $generus->nama_orang_tua }}</td>
                        <td>{{ $generus->nomor_hp_orang_tua }}</td>
                        <td><x-badge :variant="$generus->status_aktif ? 'success' : 'neutral'">{{ $generus->status_aktif ? 'Aktif' : 'Tidak Aktif' }}</x-badge></td>
                        <td class="text-right">
                            @if($bisaKelola)
                                <button type="button" wire:click="openNaikKelas({{ $generus->id }})" class="mr-3 text-xs text-ink-muted hover:text-brand-primary">Naik Kelas</button>
                                <button type="button" wire:click="openEdit({{ $generus->id }})" class="text-ink-muted hover:text-brand-primary"><x-icon name="pencil" size="16" /></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state title="Belum ada Generus" description="Tambahkan data Generus untuk mulai mencatat presensi & jurnal." icon="users" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->generusList->links() }}</div>

    <x-modal wire:model="showFormModal" :title="$editingId ? 'Edit Generus' : 'Tambah Generus'" max-width="xl">
        <form wire:submit="simpan" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Nama" name="nama" wire:model="nama" required :error="$errors->first('nama')" />
                <x-input label="Tanggal Lahir" name="tanggal_lahir" type="date" wire:model="tanggal_lahir" required :error="$errors->first('tanggal_lahir')" />
            </div>

            <div>
                <span class="form-label">Jenis Kelamin <span class="text-danger-solid">*</span></span>
                <div class="flex gap-4">
                    @foreach($jenisKelaminOptions as $kode => $label)
                        <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model="jenis_kelamin" value="{{ $kode }}"> {{ $label }}</label>
                    @endforeach
                </div>
                @error('jenis_kelamin') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            @if($bisaPilihDesa)
                <x-select label="Desa" name="desa_id" wire:model.live="desa_id" :options="$desaOptions" placeholder="Pilih desa" required :error="$errors->first('desa_id')" />
            @endif

            @if($bisaPilihKelompok)
                <x-select label="Kelompok" name="kelompok_id" wire:model.live="kelompok_id" :options="$kelompokOptions" placeholder="Pilih kelompok" required :error="$errors->first('kelompok_id')" />
            @endif

            @if($bisaPilihKelompok && ! $kelompok_id)
                <p class="text-sm text-ink-muted">Pilih Kelompok dahulu untuk memilih Jenjang.</p>
            @endif

            <x-select label="Jenjang" name="jenjang_id" wire:model="jenjang_id" :options="$jenjangOptions->pluck('label', 'id')" placeholder="Pilih jenjang" required :error="$errors->first('jenjang_id')" description="Jenjang Generus saat ini di Kelompok tersebut" />

            <div>
                <span class="form-label">Status Domisili <span class="text-danger-solid">*</span></span>
                <div class="flex gap-4">
                    @foreach($statusDomisiliOptions as $kode => $label)
                        <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="status_domisili" value="{{ $kode }}"> {{ $label }}</label>
                    @endforeach
                </div>
            </div>

            @if($status_domisili === 'PENDATANG')
                <x-info-banner>Orang tua tetap bisa memantau dari jarak jauh lewat Portal Orang Tua, atau kontak konfirmasi hasil KBM dapat diwakilkan ke PJP/Guru di Kelompok ini.</x-info-banner>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Nama Orang Tua" name="nama_orang_tua" wire:model="nama_orang_tua" required :error="$errors->first('nama_orang_tua')" />
                <x-input label="Nomor HP Orang Tua" name="nomor_hp_orang_tua" wire:model="nomor_hp_orang_tua" required :error="$errors->first('nomor_hp_orang_tua')" description="Dipakai sebagai username Portal Orang Tua" />
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="status_aktif"> Status Aktif
            </label>

            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal wire:model="showNaikKelasModal" title="Naik Kelas" max-width="sm">
        <form wire:submit="naikkanKelas" class="space-y-4">
            <x-select label="Jenjang Baru" name="naikKelasJenjangId" wire:model="naikKelasJenjangId" :options="$naikKelasOptions->pluck('label', 'id')" placeholder="Pilih jenjang baru" required :error="$errors->first('naikKelasJenjangId')" />
            <x-input label="Semester" name="naikKelasSemester" wire:model="naikKelasSemester" placeholder="mis. 2026-Ganjil" required :error="$errors->first('naikKelasSemester')" />
            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
