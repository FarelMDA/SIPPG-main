<div>
    @unless($viaToken)
        <x-page-header title="Input Presensi Kegiatan" />
    @endunless

    @if(!$berwenang)
        <x-empty-state title="Anda tidak berwenang" description="Anda tidak berwenang mencatat presensi untuk kelompok ini." icon="alert-triangle" />
    @else
        <x-card class="mb-4">
            <p class="text-sm text-ink-muted">Kegiatan</p>
            <p class="font-semibold text-ink-primary">{{ $kegiatan->nama }} — {{ $kegiatan->tanggal->format('d/m/Y') }}</p>
            <p class="mt-2 text-sm text-ink-muted">Kelompok</p>
            <p class="font-semibold text-ink-primary">{{ $kelompok->nama }}</p>
        </x-card>

        @if($kegiatan->kurikulum_kalender_id !== null)
            <x-card class="mb-4">
                <p class="text-sm text-ink-muted">Materi Terjadwal</p>
                @if(!empty($kegiatan->materi))
                    <ul class="mt-1 list-inside list-disc text-sm text-ink-primary">
                        @foreach($kegiatan->materi as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-1 text-sm text-ink-muted">Tidak ada materi terjadwal untuk kejadian ini.</p>
                @endif

                <p class="form-label mt-4">Realisasi</p>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="realisasiStatus" value="SESUAI_JADWAL"> Sesuai Jadwal</label>
                    <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="realisasiStatus" value="TIDAK_TERLAKSANA"> Tidak Terlaksana</label>
                    <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="realisasiStatus" value="PENGGANTI"> Materi Pengganti</label>
                </div>
                @if($realisasiStatus !== 'SESUAI_JADWAL')
                    <x-input label="Catatan" name="realisasiCatatan" wire:model="realisasiCatatan" class="mt-2" :error="$errors->first('realisasiCatatan')" />
                @endif
            </x-card>
        @endif

        @if($daftarGenerus->isEmpty())
            <x-empty-state title="Belum ada Generus di kelompok ini" />
        @else
            <div class="card overflow-hidden">
                <div class="divide-y divide-border-subtle">
                    @foreach($daftarGenerus as $generus)
                        <div class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between" wire:key="peserta-{{ $generus->id }}">
                            <span class="font-medium text-ink-primary">{{ $generus->nama }}</span>
                            <div class="flex gap-1">
                                @php
                                    // Warna (peerClass) tetap literal per kode di sini — Tailwind content
                                    // scanner butuh nama class utuh di source, tidak bisa dirakit dari DB.
                                    // Label teksnya bersumber dari $statusPresensiOptions (master data).
                                    $peerClass = [
                                        'HADIR' => 'peer-checked:badge-success',
                                        'IZIN' => 'peer-checked:badge-warning',
                                        'SAKIT' => 'peer-checked:badge-warning',
                                        'ALPHA' => 'peer-checked:badge-danger',
                                    ];
                                @endphp
                                @foreach($statusPresensiOptions as $kode => $label)
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model="daftar.{{ $generus->id }}" value="{{ $kode }}" class="peer sr-only">
                                        <span class="badge badge-neutral {{ $peerClass[$kode] ?? '' }} cursor-pointer">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4">
                <x-button variant="primary" size="lg" wire:click="simpan">Simpan Presensi</x-button>
            </div>
        @endif
    @endif
</div>
