<?php

namespace App\Livewire\Kegiatan;

use App\Models\Daerah;
use App\Models\Generus;
use App\Models\HariLibur;
use App\Models\JenisKegiatan;
use App\Models\KegiatanJadwal;
use App\Models\Kelas;
use App\Models\KurikulumKalender;
use App\Services\Kegiatan\BatasEkspansiTerlampauiException;
use App\Services\Kegiatan\EkspansiPolaJadwal;
use App\Services\Kegiatan\GeneratorKegiatanDariJadwal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * UC-28/UC-30 — Tambah/Ubah Jadwal Kegiatan Berulang, halaman biasa (bukan modal).
 * SRS-Fase-2 §2.2–§2.3, §2.7, §2.8, UCIC-Fase-2 UC-28/UC-29/UC-30/UC-40.
 */
#[Layout('layouts.app')]
class FormJadwalKegiatan extends Component
{
    public const HARI_OPTIONS = [
        'SENIN' => 'Senin', 'SELASA' => 'Selasa', 'RABU' => 'Rabu', 'KAMIS' => 'Kamis',
        'JUMAT' => 'Jumat', 'SABTU' => 'Sabtu', 'MINGGU' => 'Minggu',
    ];

    public const MINGGU_KE_OPTIONS = ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 'TERAKHIR' => 'Terakhir'];

    private const BATAS_KEJADIAN = 370;

    private const PESAN_BATAS_TERLAMPAUI = 'Rentang tanggal terlalu panjang — mohon persempit rentang atau buat Jadwal terpisah per periode.';

    public ?KegiatanJadwal $kegiatanJadwal = null;

    public string $nama = '';

    public string $deskripsi = '';

    public ?int $jenisKegiatanId = null;

    public string $frekuensiTipe = 'HARIAN';

    /** @var array<int, string> */
    public array $hariDalamMinggu = [];

    /** @var array<int, string> */
    public array $mingguKeDalamBulan = [];

    public ?int $intervalMinggu = null;

    public int $jumlahSesiPerKemunculan = 1;

    public string $tanggalMulai = '';

    public string $tanggalSelesai = '';

    public string $tipeTempat = 'TETAP';

    public string $tempat = '';

    /** @var array<int, string> */
    public array $rotasiTempat = [];

    public string $targetTipe = 'SEMUA';

    /** @var array<int, int> */
    public array $kelasTerpilih = [];

    /** @var array<int, int> */
    public array $individuTerpilih = [];

    public ?int $kegiatanProgramId = null;

    public bool $pratinjauSudahDihitung = false;

    public int $jumlahKejadianPratinjau = 0;

    public int $jumlahDilewatiLiburPratinjau = 0;

    /** @var array<int, string> */
    public array $contohTanggalPratinjau = [];

    public ?string $peringatanTabrakan = null;

    public ?string $errorPratinjau = null;

    public function mount(?KegiatanJadwal $kegiatanJadwal = null): void
    {
        $this->authorize('kegiatan-jadwal.manage');

        if ($kegiatanJadwal && $kegiatanJadwal->exists) {
            $this->authorizeKepemilikan($kegiatanJadwal);

            $this->kegiatanJadwal = $kegiatanJadwal;
            $this->nama = $kegiatanJadwal->nama;
            $this->deskripsi = (string) $kegiatanJadwal->deskripsi;
            $this->jenisKegiatanId = $kegiatanJadwal->jenis_kegiatan_id;
            $this->frekuensiTipe = $kegiatanJadwal->frekuensi_tipe;
            $this->hariDalamMinggu = $kegiatanJadwal->hari_dalam_minggu ?? [];
            $this->mingguKeDalamBulan = $kegiatanJadwal->minggu_ke_dalam_bulan ?? [];
            $this->intervalMinggu = $kegiatanJadwal->interval_minggu;
            $this->jumlahSesiPerKemunculan = $kegiatanJadwal->jumlah_sesi_per_kemunculan;
            $this->tanggalMulai = $kegiatanJadwal->tanggal_mulai->toDateString();
            $this->tanggalSelesai = $kegiatanJadwal->tanggal_selesai->toDateString();
            $this->tipeTempat = $kegiatanJadwal->rotasi_tempat ? 'ROTASI' : 'TETAP';
            $this->tempat = (string) $kegiatanJadwal->tempat;
            $this->rotasiTempat = $kegiatanJadwal->rotasi_tempat ?? [];
            $this->targetTipe = $kegiatanJadwal->target_tipe;
            $this->kelasTerpilih = $kegiatanJadwal->targetKelas()->pluck('kelas.id')->all();
            $this->individuTerpilih = $kegiatanJadwal->targetIndividu()->pluck('generus.id')->all();
            $this->kegiatanProgramId = $kegiatanJadwal->kegiatan_program_id;
        } else {
            $this->jumlahSesiPerKemunculan = 1;
            $this->tanggalMulai = now()->toDateString();
            $this->tanggalSelesai = now()->addMonths(3)->toDateString();
        }
    }

    /** Non admin-daerah hanya boleh mengubah Jadwal di lokasinya sendiri (pola sama FormKegiatan). */
    private function authorizeKepemilikan(KegiatanJadwal $jadwal): void
    {
        $user = Auth::guard('web')->user();

        if ($user->hasRole('admin-daerah')) {
            return;
        }

        $bolehKelola = match ($jadwal->tingkat) {
            'KELOMPOK' => $user->kelompok_id && (int) $jadwal->penyelenggara_id === (int) $user->kelompok_id,
            'DESA' => $user->desa_id && (int) $jadwal->penyelenggara_id === (int) $user->desa_id,
            default => false,
        };

        abort_unless($bolehKelola, 403);
    }

    private function tingkatSaya(): ?string
    {
        $user = Auth::guard('web')->user();

        return match (true) {
            $user->hasRole('admin-daerah') => 'DAERAH',
            $user->hasRole('pjp-desa') => 'DESA',
            $user->hasRole('pjp-kelompok') => 'KELOMPOK',
            default => null,
        };
    }

    /**
     * Tingkat & penyelenggara EFEKTIF — saat mengubah Jadwal existing, pakai tingkat
     * milik Jadwal itu sendiri (bukan tingkatSaya() acting user), supaya admin-daerah
     * yang mengubah Jadwal tingkat Kelompok tetap melihat cakupan Penargetan Peserta
     * yang benar (pola sama FormKegiatan::tingkatEfektif()).
     */
    private function tingkatEfektif(): ?string
    {
        return $this->kegiatanJadwal?->tingkat ?? $this->tingkatSaya();
    }

    private function penyelenggaraEfektif(): array
    {
        if ($this->kegiatanJadwal) {
            return [$this->kegiatanJadwal->penyelenggara_type, $this->kegiatanJadwal->penyelenggara_id];
        }

        $user = Auth::guard('web')->user();

        return match ($this->tingkatSaya()) {
            'KELOMPOK' => ['kelompok', $user->kelompok_id],
            'DESA' => ['desa', $user->desa_id],
            'DAERAH' => ['daerah', Daerah::value('id')],
            default => [null, null],
        };
    }

    public function tambahRotasiTempat(): void
    {
        $this->rotasiTempat[] = '';
    }

    public function hapusRotasiTempat(int $index): void
    {
        unset($this->rotasiTempat[$index]);
        $this->rotasiTempat = array_values($this->rotasiTempat);
    }

    #[On('target-updated')]
    public function menerimaTargetUpdate(string $targetTipe, array $kelasIds, array $individuIds): void
    {
        $this->targetTipe = $targetTipe;
        $this->kelasTerpilih = $kelasIds;
        $this->individuTerpilih = $individuIds;
        $this->batalkanPratinjau();
    }

    #[On('program-dipilih')]
    public function menerimaProgramDipilih(?int $id): void
    {
        $this->kegiatanProgramId = $id;
    }

    private function batalkanPratinjau(): void
    {
        $this->pratinjauSudahDihitung = false;
        $this->errorPratinjau = null;
    }

    public function updated($name): void
    {
        if (str($name)->startsWith(['frekuensiTipe', 'hariDalamMinggu', 'mingguKeDalamBulan', 'intervalMinggu', 'tanggalMulai', 'tanggalSelesai', 'jumlahSesiPerKemunculan', 'rotasiTempat', 'tipeTempat', 'tempat'])) {
            $this->batalkanPratinjau();
        }
    }

    private function validasiPola(): array
    {
        $data = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenisKegiatanId' => ['required', 'exists:jenis_kegiatan,id'],
            'frekuensiTipe' => ['required', 'in:HARIAN,BULANAN,MINGGUAN_INTERVAL,KURIKULUM'],
            'hariDalamMinggu' => $this->frekuensiTipe === 'KURIKULUM' ? ['array'] : ['required', 'array', 'min:1'],
            'hariDalamMinggu.*' => ['in:'.implode(',', array_keys(self::HARI_OPTIONS))],
            'mingguKeDalamBulan' => [$this->frekuensiTipe === 'BULANAN' ? 'required' : 'prohibited', 'array'],
            'intervalMinggu' => $this->frekuensiTipe === 'MINGGUAN_INTERVAL'
                ? ['required', 'integer', 'min:2']
                : ['nullable', 'prohibited'],
            'tanggalMulai' => ['required', 'date'],
            'tanggalSelesai' => ['required', 'date', 'after:tanggalMulai'],
            'rotasiTempat' => array_filter(['array', $this->tipeTempat === 'ROTASI' ? 'min:2' : null]),
        ], [
            'mingguKeDalamBulan.required' => 'Pilih minimal satu minggu-ke (atau Minggu Terakhir) untuk pola Rutin Bulanan.',
            'mingguKeDalamBulan.prohibited' => 'Minggu-ke hanya berlaku untuk pola Rutin Bulanan.',
            'intervalMinggu.required' => 'Isi interval minggu (minimal 2) untuk pola Rutin Interval Mingguan.',
            'intervalMinggu.prohibited' => 'Interval minggu hanya berlaku untuk pola Rutin Interval Mingguan.',
            'intervalMinggu.min' => 'Isi interval minggu (minimal 2) untuk pola Rutin Interval Mingguan.',
            'tanggalSelesai.after' => 'Tanggal selesai harus setelah tanggal mulai.',
            'rotasiTempat.min' => 'Isi minimal 2 tempat untuk mode Rotasi bergantian, atau gunakan Tempat tetap.',
        ]);

        // Rutin dari Kurikulum wajib satu Kelas spesifik supaya jenjang sumber breakdown
        // tidak ambigu (docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5).
        if ($this->frekuensiTipe === 'KURIKULUM' && ($this->targetTipe !== 'JENJANG_KELAS' || count($this->kelasTerpilih) !== 1)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'frekuensiTipe' => 'Rutin dari Kurikulum wajib menargetkan tepat satu Kelas lewat Penargetan Peserta.',
            ]);
        }

        return $data;
    }

    private function buildPola(): array
    {
        $pola = [
            'frekuensi_tipe' => $this->frekuensiTipe,
            'hari_dalam_minggu' => $this->hariDalamMinggu,
            'minggu_ke_dalam_bulan' => $this->frekuensiTipe === 'BULANAN' ? $this->mingguKeDalamBulan : null,
            'interval_minggu' => $this->frekuensiTipe === 'MINGGUAN_INTERVAL' ? $this->intervalMinggu : null,
            'tanggal_mulai' => CarbonImmutable::parse($this->tanggalMulai),
            'tanggal_selesai' => CarbonImmutable::parse($this->tanggalSelesai),
        ];

        if ($this->frekuensiTipe === 'KURIKULUM') {
            $pola['materi_kurikulum'] = $this->materiKurikulumUntukPratinjau();
        }

        return $pola;
    }

    /** Sama persis logikanya dengan GeneratorKegiatanDariJadwal::materiKurikulumUntukJadwal() — dipakai untuk pratinjau saja. */
    private function materiKurikulumUntukPratinjau()
    {
        if (count($this->kelasTerpilih) !== 1) {
            return collect();
        }

        $kelas = Kelas::find($this->kelasTerpilih[0]);

        if (! $kelas) {
            return collect();
        }

        return KurikulumKalender::where('jenjang', $kelas->jenjang->kode)
            ->whereDate('tanggal_selesai', '>=', $this->tanggalMulai)
            ->whereDate('tanggal_mulai', '<=', $this->tanggalSelesai)
            ->get(['tanggal_mulai', 'tanggal_selesai'])
            ->map(fn ($k) => [
                'tanggal_mulai' => CarbonImmutable::instance($k->tanggal_mulai),
                'tanggal_selesai' => CarbonImmutable::instance($k->tanggal_selesai),
            ]);
    }

    private function cekTabrakan(): ?string
    {
        $tingkat = $this->tingkatEfektif();
        [$penyelenggaraType, $penyelenggaraId] = $this->penyelenggaraEfektif();

        $lain = KegiatanJadwal::where('status', 'AKTIF')
            ->where('tingkat', $tingkat)
            ->where('penyelenggara_type', $penyelenggaraType)
            ->where('penyelenggara_id', $penyelenggaraId)
            ->when($this->kegiatanJadwal, fn ($q) => $q->whereKeyNot($this->kegiatanJadwal->id))
            ->whereDate('tanggal_mulai', '<=', $this->tanggalSelesai)
            ->whereDate('tanggal_selesai', '>=', $this->tanggalMulai)
            ->get(['hari_dalam_minggu']);

        foreach ($lain as $jadwalLain) {
            if (array_intersect($jadwalLain->hari_dalam_minggu ?? [], $this->hariDalamMinggu) !== []) {
                return 'Sudah ada Jadwal lain dengan pola serupa di sini pada hari yang sama — Anda tetap bisa melanjutkan bila ini memang disengaja.';
            }
        }

        return null;
    }

    public function hitungPratinjau(): void
    {
        $this->validasiPola();
        $this->errorPratinjau = null;

        $hasil = app(EkspansiPolaJadwal::class)->ekspansi($this->buildPola(), $this->hariLiburUntukPratinjau());
        $totalKejadian = $hasil->jumlah() * max(1, $this->jumlahSesiPerKemunculan);

        if ($totalKejadian > self::BATAS_KEJADIAN) {
            $this->errorPratinjau = self::PESAN_BATAS_TERLAMPAUI;
            $this->pratinjauSudahDihitung = false;

            return;
        }

        $this->jumlahKejadianPratinjau = $totalKejadian;
        $this->jumlahDilewatiLiburPratinjau = $hasil->jumlahDilewatiLibur;
        $this->contohTanggalPratinjau = $hasil->contohTanggal;
        $this->peringatanTabrakan = $this->cekTabrakan();
        $this->pratinjauSudahDihitung = true;
    }

    private function hariLiburUntukPratinjau()
    {
        return HariLibur::query()->get(['tanggal_mulai', 'tanggal_selesai'])
            ->map(fn ($h) => ['mulai' => CarbonImmutable::instance($h->tanggal_mulai), 'selesai' => CarbonImmutable::instance($h->tanggal_selesai)]);
    }

    /** Field pola yang sama dipakai create maupun update — menghindari duplikasi ternary kondisional. */
    private function payloadPola(): array
    {
        return [
            'jenis_kegiatan_id' => $this->jenisKegiatanId,
            'frekuensi_tipe' => $this->frekuensiTipe,
            'hari_dalam_minggu' => $this->frekuensiTipe === 'KURIKULUM' ? [] : $this->hariDalamMinggu,
            'minggu_ke_dalam_bulan' => $this->frekuensiTipe === 'BULANAN' ? $this->mingguKeDalamBulan : null,
            'interval_minggu' => $this->frekuensiTipe === 'MINGGUAN_INTERVAL' ? $this->intervalMinggu : null,
            'jumlah_sesi_per_kemunculan' => $this->jumlahSesiPerKemunculan ?: 1,
            'tanggal_mulai' => $this->tanggalMulai,
            'tanggal_selesai' => $this->tanggalSelesai,
            'tempat' => $this->tempatUntukSimpan(),
            'rotasi_tempat' => $this->rotasiUntukSimpan(),
            'target_tipe' => $this->targetTipe,
            'kegiatan_program_id' => $this->kegiatanProgramId,
        ];
    }

    private function tempatUntukSimpan(): ?string
    {
        if ($this->tipeTempat !== 'TETAP') {
            return null;
        }

        return $this->tempat ?: null;
    }

    private function rotasiUntukSimpan(): ?array
    {
        if ($this->tipeTempat !== 'ROTASI') {
            return null;
        }

        return array_values(array_filter($this->rotasiTempat));
    }

    private function kelasTargetValid(KegiatanJadwal $jadwal)
    {
        $kelompokIds = $jadwal->kelompokCakupan()->pluck('id');

        return Kelas::whereIn('id', $this->kelasTerpilih)->whereIn('kelompok_id', $kelompokIds)->pluck('id');
    }

    private function individuTargetValid(KegiatanJadwal $jadwal)
    {
        $kelompokIds = $jadwal->kelompokCakupan()->pluck('id');

        return Generus::withoutGlobalScopes()->whereIn('id', $this->individuTerpilih)
            ->whereHas('kelas', fn ($q) => $q->whereIn('kelompok_id', $kelompokIds))
            ->pluck('id');
    }

    private function simpanTargetDanProgram(KegiatanJadwal $jadwal): void
    {
        $jadwal->targetKelas()->sync($this->targetTipe === 'JENJANG_KELAS' ? $this->kelasTargetValid($jadwal) : []);
        $jadwal->targetIndividu()->sync($this->targetTipe === 'INDIVIDU' ? $this->individuTargetValid($jadwal) : []);
    }

    public function simpan(): void
    {
        $this->authorize('kegiatan-jadwal.manage');
        $this->validasiPola();

        if (! $this->pratinjauSudahDihitung) {
            $this->hitungPratinjau();

            if ($this->errorPratinjau) {
                return;
            }
        }

        $user = Auth::guard('web')->user();

        try {
            $pesan = $this->kegiatanJadwal
                ? $this->perbaruiJadwalExisting()
                : $this->buatJadwalBaru($user);
        } catch (BatasEkspansiTerlampauiException) {
            $this->dispatch('toast', variant: 'danger', message: self::PESAN_BATAS_TERLAMPAUI);

            return;
        }

        session()->flash('flash_toast', ['variant' => 'success', 'message' => $pesan]);
        $this->redirect(route('kegiatan.jadwal.index'), navigate: false);
    }

    private function buatJadwalBaru($user): string
    {
        [$penyelenggaraType, $penyelenggaraId] = $this->penyelenggaraEfektif();

        return DB::transaction(function () use ($user, $penyelenggaraType, $penyelenggaraId) {
            $jadwal = KegiatanJadwal::create([
                'nama' => $this->nama,
                'deskripsi' => $this->deskripsi ?: null,
                'tingkat' => $this->tingkatSaya(),
                'penyelenggara_type' => $penyelenggaraType,
                'penyelenggara_id' => $penyelenggaraId,
                ...$this->payloadPola(),
                'status' => 'AKTIF',
                'dibuat_oleh' => $user->id,
            ]);

            $this->simpanTargetDanProgram($jadwal);

            $hasilGenerate = app(GeneratorKegiatanDariJadwal::class)->generate($jadwal);

            activity('kegiatan-jadwal')->causedBy($user)->performedOn($jadwal)->log('Jadwal Kegiatan Berulang disimpan');

            return "Jadwal berhasil disimpan, {$hasilGenerate->jumlahDibuat} Kegiatan dibuat.";
        });
    }

    private function perbaruiJadwalExisting(): string
    {
        $jadwal = $this->kegiatanJadwal;
        $this->authorizeKepemilikan($jadwal);

        return DB::transaction(function () use ($jadwal) {
            $jadwal->update([
                'nama' => $this->nama,
                'deskripsi' => $this->deskripsi ?: null,
                ...$this->payloadPola(),
            ]);

            $this->simpanTargetDanProgram($jadwal);

            app(GeneratorKegiatanDariJadwal::class)->generate($jadwal, hanyaMulaiDari: CarbonImmutable::today());

            activity('kegiatan-jadwal')->causedBy(Auth::guard('web')->user())->performedOn($jadwal)->log('Jadwal Kegiatan Berulang diperbarui');

            return 'Jadwal diperbarui, kejadian mendatang telah disesuaikan.';
        });
    }

    public function render()
    {
        $tingkat = $this->tingkatEfektif();
        [, $penyelenggaraId] = $this->penyelenggaraEfektif();

        return view('livewire.kegiatan.form-jadwal-kegiatan', [
            'jenisOptions' => JenisKegiatan::options(),
            'hariOptions' => self::HARI_OPTIONS,
            'mingguKeOptions' => self::MINGGU_KE_OPTIONS,
            'tingkatSaya' => $tingkat,
            'penyelenggaraIdSaya' => $penyelenggaraId,
        ]);
    }
}
