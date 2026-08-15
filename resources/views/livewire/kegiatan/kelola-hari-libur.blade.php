<div>
    <x-page-header title="Kelola Kalender Hari Libur">
        <x-slot:actions>
            <x-button variant="primary" icon="plus" wire:click="openCreate">Tambah Hari Libur</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-x-auto p-0">
        <table class="data-table">
            <thead><tr><th>Nama</th><th>Tanggal Mulai</th><th>Tanggal Selesai</th><th>Sumber</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse($this->daftar as $libur)
                    <tr wire:key="libur-{{ $libur->id }}">
                        <td class="font-medium text-ink-primary">{{ $libur->nama }}</td>
                        <td>{{ $libur->tanggal_mulai->format('d/m/Y') }}</td>
                        <td>{{ $libur->tanggal_selesai->format('d/m/Y') }}</td>
                        <td><x-status-badge :status="$libur->sumber" /></td>
                        <td class="text-right space-x-2">
                            <button type="button" wire:click="openEdit({{ $libur->id }})" class="text-brand-primary hover:underline">Ubah</button>
                            <button type="button"
                                wire:click="hapus({{ $libur->id }})"
                                wire:confirm="{{ $libur->sumber === 'OTOMATIS_GOOGLE' ? 'Baris ini disembunyikan dan tidak akan muncul lagi otomatis dari sinkronisasi berikutnya. Lanjutkan?' : 'Menghapus Hari Libur ini tidak mengembalikan Kegiatan yang sebelumnya sudah otomatis dibatalkan. Lanjutkan?' }}"
                                class="text-danger-text hover:underline">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="Belum ada Hari Libur tercatat" icon="calendar-x" /></td></tr>
                @endforelse
            </tbody>
        </table>
        <p class="border-t border-border px-4 py-3 text-xs text-ink-muted">
            Hari libur nasional bisa tersinkron otomatis dari Google Calendar tiap bulan — libur internal organisasi tetap perlu ditambahkan manual.
        </p>
    </div>

    <x-modal wire:model="showFormModal" title="{{ $editingId ? 'Ubah' : 'Tambah' }} Hari Libur">
        <form wire:submit="simpan" class="space-y-4">
            @if($sumberEditing === 'OTOMATIS_GOOGLE')
                <x-info-banner variant="warning">Baris ini berasal dari sinkronisasi Google Calendar. Perubahan yang Anda simpan tidak akan tertimpa oleh sinkronisasi berikutnya.</x-info-banner>
            @endif
            <x-input label="Nama" name="nama" wire:model="nama" required :error="$errors->first('nama')" />
            <x-input label="Tanggal Mulai" name="tanggalMulai" type="date" wire:model="tanggalMulai" required :error="$errors->first('tanggalMulai')" />
            <x-input label="Tanggal Selesai" name="tanggalSelesai" type="date" wire:model="tanggalSelesai" required :error="$errors->first('tanggalSelesai')" />

            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                <x-button type="submit" variant="primary">Simpan</x-button>
            </div>
        </form>
    </x-modal>
</div>
