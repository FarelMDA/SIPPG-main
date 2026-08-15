<div>
    <x-page-header title="Kelola Petugas Presensi Kegiatan" :description="$kegiatan->nama.' — '.$kegiatan->tanggal->format('d/m/Y')" />

    @if($kelompokCakupanOptions->count() > 1)
        <div class="mb-6 max-w-xs">
            <x-select label="Kelompok" name="kelompokCakupanId" wire:model.live="kelompokCakupanId" :options="$kelompokCakupanOptions" />
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card>
            <h2 class="mb-4 text-lg font-semibold text-ink-primary">Petugas Ditunjuk</h2>
            <div class="space-y-2">
                @forelse($this->petugasList as $petugas)
                    <div class="flex items-center justify-between rounded-md border border-border p-3 text-sm" wire:key="petugas-{{ $petugas->id }}">
                        <div>
                            <span class="font-medium text-ink-primary">{{ $petugas->user?->nama ?? $petugas->generus?->nama }}</span>
                            <x-badge variant="neutral">{{ $petugas->user_id ? 'Akun Internal' : 'Generus' }}</x-badge>
                            @if($petugas->generus_id)
                                <x-badge :variant="$petugas->tokenMasihBerlaku() ? 'success' : 'danger'">{{ $petugas->tokenMasihBerlaku() ? 'Token Aktif' : 'Kedaluwarsa' }}</x-badge>
                            @endif
                        </div>
                        <button type="button" wire:click="cabut({{ $petugas->id }})" wire:confirm="Cabut penugasan ini?" class="text-danger-text hover:underline">Cabut</button>
                    </div>
                @empty
                    <x-empty-state title="Belum ada petugas ditunjuk" />
                @endforelse
            </div>
        </x-card>

        <x-card>
            <h2 class="mb-4 text-lg font-semibold text-ink-primary">Tunjuk Petugas Baru</h2>
            <form wire:submit="tunjuk" class="space-y-4">
                <div>
                    <span class="form-label">Tipe Petugas</span>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="tipe_petugas" value="AKUN_INTERNAL"> Akun Internal</label>
                        <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="tipe_petugas" value="GENERUS"> Generus</label>
                    </div>
                </div>

                @if($tipe_petugas === 'AKUN_INTERNAL')
                    <x-select label="Pilih Akun" name="user_id" wire:model="user_id" :options="$userOptions" placeholder="Pilih Sekretaris/Guru" :error="$errors->first('user_id')" />
                @else
                    <x-select label="Pilih Generus" name="generus_id" wire:model="generus_id" :options="$generusOptions" placeholder="Pilih Generus" :error="$errors->first('generus_id')" />
                @endif

                <x-button type="submit" variant="primary">Tunjuk Petugas</x-button>
            </form>

            @if($tautanBaru)
                <div class="mt-4 rounded-md border border-warning-border bg-warning-bg p-3" x-data="{ copied: false }">
                    <p class="mb-2 text-sm text-warning-text">Tautan Presensi Kegiatan (tampil sekali):</p>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ $tautanBaru }}" class="form-control flex-1 text-xs" x-ref="tautan">
                        <button type="button" class="btn btn-sm btn-secondary" @click="navigator.clipboard.writeText($refs.tautan.value); copied = true; setTimeout(() => copied = false, 2000)">
                            <span x-show="!copied">Salin</span><span x-show="copied" x-cloak>Tersalin!</span>
                        </button>
                    </div>
                    <x-info-banner variant="warning" class="mt-2">Salin dan sampaikan tautan ini secara manual, tidak dapat dilihat ulang.</x-info-banner>
                </div>
            @endif
        </x-card>
    </div>
</div>
