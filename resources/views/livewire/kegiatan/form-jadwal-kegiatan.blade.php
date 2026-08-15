<div>
    <x-page-header :title="$kegiatanJadwal ? 'Ubah Jadwal Kegiatan Berulang' : 'Tambah Jadwal Kegiatan Berulang'" />

    <x-card class="max-w-2xl">
        <form wire:submit="simpan" class="space-y-4">
            <div class="rounded-md bg-surface-subtle px-3 py-2 text-sm">
                Tingkat: <x-status-badge :status="$tingkatSaya" />
            </div>

            <x-input label="Nama" name="nama" wire:model="nama" required :error="$errors->first('nama')" />
            <x-input label="Deskripsi" name="deskripsi" wire:model="deskripsi" />
            <x-select label="Jenis" name="jenisKegiatanId" wire:model="jenisKegiatanId" :options="$jenisOptions" placeholder="Pilih Jenis" required :error="$errors->first('jenisKegiatanId')" />

            <div>
                <span class="form-label">Frekuensi</span>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="frekuensiTipe" value="HARIAN"> Rutin Harian</label>
                    <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="frekuensiTipe" value="BULANAN"> Rutin Bulanan</label>
                    <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="frekuensiTipe" value="MINGGUAN_INTERVAL"> Rutin Interval Mingguan</label>
                    <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="frekuensiTipe" value="KURIKULUM"> Rutin dari Kurikulum (KBM)</label>
                </div>
            </div>

            @if($frekuensiTipe === 'KURIKULUM')
                <x-info-banner>Tanggal kejadian mengikuti breakdown Kurikulum untuk jenjang Kelas yang ditarget di bawah — wajib pilih tepat satu Kelas lewat Penargetan Peserta.</x-info-banner>
                @error('frekuensiTipe') <p class="form-error">{{ $message }}</p> @enderror
            @else
                <div>
                    <span class="form-label">Hari dalam Minggu</span>
                    <div class="flex flex-wrap gap-1">
                        @foreach($hariOptions as $kode => $label)
                            <label class="badge {{ in_array($kode, $hariDalamMinggu) ? 'badge-info' : 'badge-neutral' }}">
                                <input type="checkbox" wire:model.live="hariDalamMinggu" value="{{ $kode }}" class="sr-only"> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('hariDalamMinggu') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            @endif

            @if($frekuensiTipe === 'BULANAN')
                <div>
                    <span class="form-label">Minggu ke dalam Bulan</span>
                    <div class="flex flex-wrap gap-1">
                        @foreach($mingguKeOptions as $nilai => $label)
                            <label class="badge {{ in_array($nilai, $mingguKeDalamBulan) ? 'badge-info' : 'badge-neutral' }}">
                                <input type="checkbox" wire:model.live="mingguKeDalamBulan" value="{{ $nilai }}" class="sr-only"> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('mingguKeDalamBulan') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            @elseif($frekuensiTipe === 'MINGGUAN_INTERVAL')
                <x-input label="Interval Minggu (mis. 2 = tiap 2 minggu sekali)" name="intervalMinggu" type="number" min="2" wire:model.live="intervalMinggu" :error="$errors->first('intervalMinggu')" />
            @endif

            <x-input label="Jumlah Sesi per Kemunculan" name="jumlahSesiPerKemunculan" type="number" min="1" wire:model.live="jumlahSesiPerKemunculan" />

            <div class="grid grid-cols-2 gap-4">
                <x-input label="Tanggal Mulai" name="tanggalMulai" type="date" wire:model.live="tanggalMulai" required :error="$errors->first('tanggalMulai')" />
                <x-input label="Tanggal Selesai" name="tanggalSelesai" type="date" wire:model.live="tanggalSelesai" required :error="$errors->first('tanggalSelesai')" />
            </div>

            <div>
                <span class="form-label">Tempat</span>
                <div class="mb-2 flex gap-4">
                    <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="tipeTempat" value="TETAP"> Tempat tetap</label>
                    <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model.live="tipeTempat" value="ROTASI"> Rotasi bergantian</label>
                </div>

                @if($tipeTempat === 'TETAP')
                    <x-input name="tempat" wire:model="tempat" placeholder="Nama tempat" />
                @else
                    <div class="space-y-2">
                        @foreach($rotasiTempat as $index => $lokasi)
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-ink-muted">{{ $index + 1 }}.</span>
                                <input type="text" wire:model.live="rotasiTempat.{{ $index }}" class="form-control flex-1" placeholder="Nama tempat">
                                <button type="button" wire:click="hapusRotasiTempat({{ $index }})" class="text-danger-text">✕</button>
                            </div>
                        @endforeach
                        <x-button type="button" variant="secondary" size="sm" wire:click="tambahRotasiTempat">+ Tambah tempat</x-button>
                        <p class="form-description">Kejadian berurutan akan memakai tempat di atas secara bergantian, berulang dari awal setelah daftar habis.</p>
                        @error('rotasiTempat') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

            <livewire:kegiatan.kelola-penargetan-peserta
                :tingkat="$tingkatSaya"
                :penyelenggara-id="$penyelenggaraIdSaya"
                :target-tipe="$targetTipe"
                :kelas-awal="$kelasTerpilih"
                :individu-awal="$individuTerpilih"
                wire:key="penargetan-{{ $kegiatanJadwal?->id ?? 'baru' }}"
            />

            <livewire:kegiatan.kelola-program-kegiatan
                :kegiatan-program-id="$kegiatanProgramId"
                wire:key="program-{{ $kegiatanJadwal?->id ?? 'baru' }}"
            />

            @if($pratinjauSudahDihitung)
                <div class="rounded-md border border-border bg-surface-subtle p-4">
                    <p class="font-medium text-ink-primary">Pratinjau: pola ini akan menghasilkan {{ $jumlahKejadianPratinjau }} Kegiatan</p>
                    <p class="text-sm text-ink-muted">dari {{ \Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y') }} s.d. {{ \Carbon\Carbon::parse($tanggalSelesai)->format('d/m/Y') }}</p>
                    <p class="text-sm text-ink-muted">Contoh tanggal: {{ implode(', ', $contohTanggalPratinjau) }}</p>
                    @if($jumlahDilewatiLiburPratinjau > 0)
                        <x-info-banner class="mt-2">{{ $jumlahDilewatiLiburPratinjau }} tanggal yang cocok pola dilewati karena termasuk Hari Libur.</x-info-banner>
                    @endif
                    @if($peringatanTabrakan)
                        <x-info-banner variant="warning" class="mt-2">{{ $peringatanTabrakan }}</x-info-banner>
                    @endif
                </div>
            @elseif($errorPratinjau)
                <x-info-banner variant="danger">{{ $errorPratinjau }}</x-info-banner>
            @else
                <x-info-banner>Isi pola dan rentang tanggal, lalu klik Hitung Pratinjau untuk melihat jumlah Kegiatan yang akan dibuat sebelum menyimpan.</x-info-banner>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" wire:click="hitungPratinjau">Hitung Pratinjau</x-button>
                <a href="{{ route('kegiatan.jadwal.index') }}" class="btn btn-md btn-secondary">Batal</a>
                <x-button type="submit" variant="primary">{{ $kegiatanJadwal ? 'Simpan Perubahan' : 'Konfirmasi & Simpan Jadwal' }}</x-button>
            </div>
        </form>
    </x-card>
</div>
