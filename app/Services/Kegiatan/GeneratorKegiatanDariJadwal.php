<?php

namespace App\Services\Kegiatan;

use App\Models\HariLibur;
use App\Models\Kegiatan;
use App\Models\KegiatanJadwal;
use App\Models\KurikulumKalender;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Orkestrasi generate & regenerasi kejadian `Kegiatan` dari `KegiatanJadwal` (SRS-Fase-2 §2.3,
 * UC-30). Dipakai baik untuk Jadwal baru (`$hanyaMulaiDari = null`, seluruh rentang
 * tanggal_mulai..tanggal_selesai) maupun regenerasi Jadwal yang diedit (`$hanyaMulaiDari =
 * today()`, hanya kejadian mendatang & belum ada presensi yang diganti — SRS §2.3).
 */
class GeneratorKegiatanDariJadwal
{
    private const BATAS_KEJADIAN = 370;

    public function __construct(private readonly EkspansiPolaJadwal $ekspansi) {}

    public function generate(KegiatanJadwal $jadwal, ?CarbonImmutable $hanyaMulaiDari = null): HasilGenerate
    {
        $tanggalMulaiJadwal = CarbonImmutable::instance($jadwal->tanggal_mulai);
        $efektifMulai = ($hanyaMulaiDari !== null && $hanyaMulaiDari->gt($tanggalMulaiJadwal))
            ? $hanyaMulaiDari
            : $tanggalMulaiJadwal;

        $tanggalSelesaiJadwal = CarbonImmutable::instance($jadwal->tanggal_selesai);

        $pola = [
            'frekuensi_tipe' => $jadwal->frekuensi_tipe,
            'hari_dalam_minggu' => $jadwal->hari_dalam_minggu,
            'minggu_ke_dalam_bulan' => $jadwal->minggu_ke_dalam_bulan,
            'interval_minggu' => $jadwal->interval_minggu,
            'tanggal_mulai' => $efektifMulai,
            'tanggal_selesai' => $tanggalSelesaiJadwal,
        ];

        if ($jadwal->frekuensi_tipe === 'KURIKULUM') {
            $pola['materi_kurikulum'] = $this->materiKurikulumUntukJadwal($jadwal, $efektifMulai, $tanggalSelesaiJadwal);
        }

        $hasil = $this->ekspansi->ekspansi($pola, $this->hariLiburAktif());

        $totalKejadian = $hasil->jumlah() * max(1, $jadwal->jumlah_sesi_per_kemunculan);

        if ($totalKejadian > self::BATAS_KEJADIAN) {
            throw new BatasEkspansiTerlampauiException($totalKejadian);
        }

        $materiKurikulum = $pola['materi_kurikulum'] ?? collect();

        return DB::transaction(function () use ($jadwal, $hasil, $efektifMulai, $materiKurikulum) {
            $this->hapusEventAmanUntukDiganti($jadwal, $efektifMulai);
            $jumlahDibuat = $this->buatEvents($jadwal, $hasil->tanggalDibuat, $materiKurikulum);

            return new HasilGenerate($jumlahDibuat, $hasil->jumlahDilewatiLibur);
        });
    }

    /**
     * Baris `kurikulum_kalender` untuk jenjang Kelas target Jadwal (frekuensi_tipe=KURIKULUM
     * mewajibkan target_tipe=JENJANG_KELAS dengan tepat satu Kelas — divalidasi di
     * FormJadwalKegiatan, bukan di sini) yang beririsan dengan jendela tanggal Jadwal.
     *
     * @return Collection<int, array{id: int, tanggal_mulai: CarbonImmutable, tanggal_selesai: CarbonImmutable, item_materi: array|null, jenis: string}>
     */
    private function materiKurikulumUntukJadwal(KegiatanJadwal $jadwal, CarbonImmutable $mulai, CarbonImmutable $selesai): Collection
    {
        $kelas = $jadwal->targetKelas()->first();

        if (! $kelas) {
            return collect();
        }

        return KurikulumKalender::where('jenjang', $kelas->jenjang->kode)
            ->whereDate('tanggal_selesai', '>=', $mulai->toDateString())
            ->whereDate('tanggal_mulai', '<=', $selesai->toDateString())
            ->get(['id', 'tanggal_mulai', 'tanggal_selesai', 'item_materi', 'jenis'])
            ->map(fn (KurikulumKalender $k) => [
                'id' => $k->id,
                'tanggal_mulai' => CarbonImmutable::instance($k->tanggal_mulai),
                'tanggal_selesai' => CarbonImmutable::instance($k->tanggal_selesai),
                'item_materi' => $k->item_materi,
                'jenis' => $k->jenis,
            ]);
    }

    /**
     * Hapus kejadian mendatang (>= $sejakTanggal) milik Jadwal ini yang BELUM ada baris
     * kegiatan_peserta (belum ada presensi tercatat) — histori presensi yang sudah
     * tercatat, atau kejadian yang sudah lewat, tidak pernah tersentuh (SRS §2.3).
     */
    private function hapusEventAmanUntukDiganti(KegiatanJadwal $jadwal, CarbonImmutable $sejakTanggal): void
    {
        Kegiatan::where('kegiatan_jadwal_id', $jadwal->id)
            ->where('tanggal', '>=', $sejakTanggal->toDateString())
            ->doesntHave('peserta')
            ->delete();
    }

    /**
     * @param  list<CarbonImmutable>  $tanggalDibuat
     * @param  Collection<int, array{id: int, tanggal_mulai: CarbonImmutable, tanggal_selesai: CarbonImmutable, item_materi: array|null, jenis: string}>  $materiKurikulum
     */
    private function buatEvents(KegiatanJadwal $jadwal, array $tanggalDibuat, Collection $materiKurikulum): int
    {
        $pakaiRotasi = ! empty($jadwal->rotasi_tempat);
        $idBaru = [];

        foreach ($tanggalDibuat as $tanggal) {
            $materi = $materiKurikulum->first(
                fn (array $k) => $tanggal->gte($k['tanggal_mulai']) && $tanggal->lte($k['tanggal_selesai'])
            );

            for ($sesi = 1; $sesi <= max(1, $jadwal->jumlah_sesi_per_kemunculan); $sesi++) {
                $kegiatan = Kegiatan::create([
                    'nama' => $jadwal->nama,
                    'deskripsi' => $jadwal->deskripsi,
                    'tingkat' => $jadwal->tingkat,
                    'penyelenggara_type' => $jadwal->penyelenggara_type,
                    'penyelenggara_id' => $jadwal->penyelenggara_id,
                    'jenis_kegiatan_id' => $jadwal->jenis_kegiatan_id,
                    'tanggal' => $tanggal->toDateString(),
                    'sesi_ke' => $sesi,
                    'tempat' => $pakaiRotasi ? null : $jadwal->tempat,
                    'target_tipe' => $jadwal->target_tipe,
                    'kegiatan_jadwal_id' => $jadwal->id,
                    'kegiatan_program_id' => $jadwal->kegiatan_program_id,
                    'status' => 'TERJADWAL',
                    'dibuat_oleh' => $jadwal->dibuat_oleh,
                    // KBM-sebagai-Kegiatan (docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5) —
                    // materi di-SNAPSHOT saat generate, bukan rujukan dinamis ke kurikulum_kalender,
                    // pola sama snapshot target_kelas/target_individu di salinTarget().
                    'kurikulum_kalender_id' => $materi['id'] ?? null,
                    'materi' => $materi['item_materi'] ?? null,
                    'realisasi_status' => $materi ? 'SESUAI_JADWAL' : null,
                ]);

                $idBaru[] = $kegiatan->id;
                $this->salinTarget($jadwal, $kegiatan);
            }
        }

        if ($pakaiRotasi) {
            $this->terapkanRotasiTempat($jadwal, $idBaru);
        }

        return count($idBaru);
    }

    /** Salin snapshot penargetan dari Jadwal ke kejadian baru (SRS §2.4) — bukan rujukan dinamis. */
    private function salinTarget(KegiatanJadwal $jadwal, Kegiatan $kegiatan): void
    {
        if ($jadwal->target_tipe === 'JENJANG_KELAS') {
            $kelasIds = $jadwal->targetKelas()->pluck('kelas.id');

            if ($kelasIds->isNotEmpty()) {
                DB::table('kegiatan_target_kelas')->insert(
                    $kelasIds->map(fn ($id) => ['kegiatan_id' => $kegiatan->id, 'kelas_id' => $id])->all()
                );
            }
        } elseif ($jadwal->target_tipe === 'INDIVIDU') {
            $generusIds = $jadwal->targetIndividu()->pluck('generus.id');

            if ($generusIds->isNotEmpty()) {
                DB::table('kegiatan_target_individu')->insert(
                    $generusIds->map(fn ($id) => ['kegiatan_id' => $kegiatan->id, 'generus_id' => $id])->all()
                );
            }
        }
    }

    /**
     * Hitung ulang indeks rotasi dari urutan penuh kejadian aktif Jadwal ini (lampau +
     * baru), bukan counter tersimpan — kejadian lampau TIDAK ditulis ulang, cuma dihitung
     * posisinya supaya kejadian baru mendapat tempat yang konsisten (SRS §2.7).
     *
     * @param  list<int>  $idBaru
     */
    private function terapkanRotasiTempat(KegiatanJadwal $jadwal, array $idBaru): void
    {
        $rotasi = $jadwal->rotasi_tempat;
        $jumlahRotasi = count($rotasi);

        if ($jumlahRotasi === 0) {
            return;
        }

        $idBaruSet = array_flip($idBaru);

        Kegiatan::where('kegiatan_jadwal_id', $jadwal->id)
            ->orderBy('tanggal')
            ->orderBy('sesi_ke')
            ->get(['id'])
            ->values()
            ->each(function (Kegiatan $kegiatan, int $indeks) use ($idBaruSet, $rotasi, $jumlahRotasi) {
                if (isset($idBaruSet[$kegiatan->id])) {
                    $kegiatan->update(['tempat' => $rotasi[$indeks % $jumlahRotasi]]);
                }
            });
    }

    /** @return Collection<int, array{mulai: CarbonImmutable, selesai: CarbonImmutable}> */
    private function hariLiburAktif(): Collection
    {
        return HariLibur::query()
            ->get(['tanggal_mulai', 'tanggal_selesai'])
            ->map(fn (HariLibur $libur) => [
                'mulai' => CarbonImmutable::instance($libur->tanggal_mulai),
                'selesai' => CarbonImmutable::instance($libur->tanggal_selesai),
            ]);
    }
}
