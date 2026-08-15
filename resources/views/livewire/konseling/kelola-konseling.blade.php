<div>
    <x-page-header title="Catatan Konseling" description="Rekam kasus generus — akses terbatas (PRD §11.1)">
        @if($bisaTulis)
            <x-slot:actions>
                <x-button variant="primary" icon="plus" wire:click="openCreate">Catat Konseling Baru</x-button>
            </x-slot:actions>
        @endif
    </x-page-header>

    <x-info-banner variant="warning">Data ini sensitif — hanya terlihat oleh Bagian BK, PJP Kelompok, dan Admin Daerah. Tidak ditampilkan ke Guru/Sekretaris KBM lain.</x-info-banner>

    <div class="card mt-4 overflow-x-auto p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Generus</th>
                    <th>Catatan</th>
                    <th>Status</th>
                    <th>Dicatat Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->daftar as $c)
                    <tr wire:key="konsel-{{ $c->id }}">
                        <td>{{ $c->tanggal->format('d/m/Y') }}</td>
                        <td class="font-medium text-ink-primary">{{ $c->generus->nama }}</td>
                        <td class="max-w-md truncate">{{ $c->catatan }}</td>
                        <td><x-badge :variant="$c->status === 'SELESAI' ? 'success' : 'info'">{{ $statusOptions[$c->status] }}</x-badge></td>
                        <td>{{ $c->dicatatOleh?->nama }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="Belum ada catatan konseling" icon="users" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($bisaTulis)
        <x-modal wire:model="showFormModal" title="Catat Konseling Baru">
            <form wire:submit="simpan" class="space-y-4">
                <x-select label="Generus" name="generus_id" wire:model="generus_id" :options="$this->generusOptions" placeholder="Pilih generus" required :error="$errors->first('generus_id')" />
                <x-input label="Tanggal" name="tanggal" type="date" wire:model="tanggal" required :error="$errors->first('tanggal')" />

                <div>
                    <label class="form-label">Catatan</label>
                    <textarea wire:model="catatan" class="form-control" rows="4"></textarea>
                    @error('catatan') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <x-select label="Status" name="status" wire:model="status" :options="$statusOptions" required :error="$errors->first('status')" />

                <div class="flex justify-end gap-2 pt-2">
                    <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                    <x-button type="submit" variant="primary">Simpan</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
