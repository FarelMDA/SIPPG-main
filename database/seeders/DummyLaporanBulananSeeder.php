<?php

namespace Database\Seeders;

use App\Models\Daerah;
use App\Models\Desa;
use App\Models\Generus;
use App\Models\JenisKegiatan;
use App\Models\JenisMusyawaroh;
use App\Models\Jenjang;
use App\Models\Kegiatan;
use App\Models\KegiatanPeserta;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\LaporanBulanan;
use App\Models\Musyawaroh;
use App\Models\MusyawarohItem;
use App\Models\ProgramMonitoring;
use App\Models\ProgramMonitoringItem;
use App\Models\SensusSnapshot;
use App\Models\User;
use App\Services\Laporan\AgregasiLaporanDaerah;
use App\Services\Laporan\AgregasiLaporanDesa;
use App\Services\Laporan\SusunSnapshotLaporan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data dummy khusus untuk mengisi SELURUH halaman Laporan Bulanan (SRS-Fase-2 §3, §3.4,
 * §3.5) — Laporan Kelompok, Laporan Desa, Laporan Daerah, Antrian Approval, dan Telusur
 * Laporan Berjenjang — supaya bisa didemokan/diuji tanpa harus mengisi manual dulu lewat
 * UI. BUKAN bagian dari seeding baseline (`DatabaseSeeder`). Jalankan manual:
 *   php artisan db:seed --class=DummyLaporanBulananSeeder
 *
 * Scope-nya SENGAJA terpisah dari DummyPondokJayaSeeder (4 Kelompok baru: BIG 1, BIG 2,
 * Bintaro Jaya Barat, Bintaro Jaya Timur — 2 Desa: Bumi Indah Grogol, Bintaro Jaya) supaya
 * reset (lihat App\Console\Commands\ResetDummyLaporan, `php artisan dummy:laporan-reset`)
 * bisa menghapus persis apa yang seeder ini buat tanpa risiko ikut menghapus data Pondok
 * Jaya atau data asli yang mungkin sudah diisi manual oleh pengguna.
 *
 * Idempotent: `run()` selalu menghapus data lamanya sendiri (hapusData()) lalu membangun
 * ulang dari nol — aman dijalankan berulang, hasilnya selalu konsisten dengan `now()` saat
 * dijalankan (3 periode: 2 bulan lalu, bulan lalu, bulan berjalan).
 */
class DummyLaporanBulananSeeder extends Seeder
{
    private const KELOMPOK_IDS = [1, 2, 7, 8]; // BIG 1, BIG 2 (Bumi Indah Grogol); Bintaro Jaya Barat, Bintaro Jaya Timur (Bintaro Jaya)

    private const DESA_IDS = [1, 2]; // Bumi Indah Grogol, Bintaro Jaya

    private const DAERAH_ID = 1;

    private const USERNAME_DUMMY = [
        'pjp.kelompok.big1', 'pjp.kelompok.big2',
        'pjp.kelompok.bintarojayabarat', 'pjp.kelompok.bintarojayatimur',
        'pjp.desa.bumiindahgrogol', 'pjp.desa.bintarojaya',
    ];

    // Distribusi ringan per Kelompok — cukup untuk mengisi slide Sensus & Daftar Generus,
    // tidak selengkap DummyPondokJayaSeeder (yang memang didesain sebagai showcase Fase 1).
    private const JENJANG_DUMMY = [
        'DASAR_1' => ['total' => 6, 'pendatang' => 1, 'usia' => 6],
        'DASAR_3' => ['total' => 5, 'pendatang' => 2, 'usia' => 8],
        'MENENGAH_7' => ['total' => 4, 'pendatang' => 0, 'usia' => 12],
        'GPN_A' => ['total' => 5, 'pendatang' => 2, 'usia' => 20],
    ];

    public function run(): void
    {
        $this->hapusData();

        $faker = fake('id_ID');
        $jenjangIds = Jenjang::pluck('id', 'kode');
        $kelompokList = Kelompok::whereIn('id', self::KELOMPOK_IDS)->get()->keyBy('id');

        $kelas = $kelompokList->mapWithKeys(fn (Kelompok $k) => [
            $k->id => collect(self::JENJANG_DUMMY)->mapWithKeys(fn ($_, $kode) => [
                $kode => Kelas::create(['kelompok_id' => $k->id, 'jenjang_id' => $jenjangIds[$kode], 'status_aktif' => true]),
            ]),
        ]);

        foreach ($kelompokList as $kelompok) {
            foreach (self::JENJANG_DUMMY as $kode => $data) {
                $this->buatSiswa($kelas[$kelompok->id][$kode], $faker, $data['total'], $data['pendatang'], $data['usia']);
            }
        }

        $pjpKelompok = $kelompokList->mapWithKeys(fn (Kelompok $k) => [$k->id => $this->provisionPjpKelompok($k)]);
        $pjpDesa = Desa::whereIn('id', self::DESA_IDS)->get()->mapWithKeys(fn (Desa $d) => [$d->id => $this->provisionPjpDesa($d)]);
        $admin = User::where('username', 'admin')->firstOrFail();
        $daerah = Daerah::findOrFail(self::DAERAH_ID);

        $periodeP2 = now()->subMonths(2)->format('Y-m');
        $periodeP1 = now()->subMonth()->format('Y-m');
        $periodeP0 = now()->format('Y-m');

        foreach ($kelompokList as $kelompok) {
            foreach ([$periodeP2, $periodeP1, $periodeP0] as $periode) {
                $this->isiDataSumber($kelompok, $pjpKelompok[$kelompok->id], $periode, $faker);
            }
        }

        foreach (self::DESA_IDS as $desaId) {
            foreach ([$periodeP2, $periodeP1] as $periode) {
                $this->isiMusyawarohDesa(Desa::find($desaId), $periode);
            }
        }

        foreach ([$periodeP2, $periodeP1] as $periode) {
            $this->isiMusyawarohDaerah($daerah, $periode);
        }

        foreach ([$periodeP2, $periodeP1, $periodeP0] as $periode) {
            (new \App\Jobs\HitungSensusSnapshotBulanan($periode))->handle();
        }

        // --- Laporan Kelompok — 3 periode: DISETUJUI (lama), FINAL menunggu review, DRAFT (live) ---
        foreach ($kelompokList as $kelompok) {
            $pjk = $pjpKelompok[$kelompok->id];
            $pjd = $pjpDesa[$kelompok->desa_id];

            if ($kelompok->id === 1) {
                // BIG 1 — demo siklus revisi: v1 ditolak, v2 disetujui (§3.1, riwayat versi P-LAP-01).
                $v1 = $this->buatLaporanKelompokFinal($kelompok, $periodeP2, 1, $pjk);
                $v1->update(['status' => 'REVISI_DIMINTA', 'catatan_revisi' => 'Grafik kehadiran belum sesuai, mohon periksa ulang data presensi bulan ini.']);
                $v2 = $this->buatLaporanKelompokFinal($kelompok, $periodeP2, 2, $pjk);
                $v2->update(['status' => 'DISETUJUI', 'disetujui_oleh' => $pjd->id, 'disetujui_pada' => now()]);
            } else {
                $laporan = $this->buatLaporanKelompokFinal($kelompok, $periodeP2, 1, $pjk);
                $laporan->update(['status' => 'DISETUJUI', 'disetujui_oleh' => $pjd->id, 'disetujui_pada' => now()]);
            }

            $this->buatLaporanKelompokFinal($kelompok, $periodeP1, 1, $pjk); // FINAL, menunggu review PJP Desa

            LaporanBulanan::create([
                'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => $periodeP0, 'versi' => 1,
                'status' => 'DRAFT', 'dibuat_oleh' => $pjk->id,
            ]);
        }

        // --- Laporan Desa — agregasi dari Laporan Kelompok yang baru dibuat di atas (§3.4) ---
        foreach (self::DESA_IDS as $desaId) {
            $desa = Desa::find($desaId);
            $pjd = $pjpDesa[$desaId];

            $laporanP2 = $this->buatLaporanDesa($desa, $periodeP2, 1, $pjd);
            $laporanP2->update(['status' => 'DISETUJUI', 'disetujui_oleh' => $admin->id, 'disetujui_pada' => now()]);

            $this->buatLaporanDesa($desa, $periodeP1, 1, $pjd); // FINAL, menunggu review Admin Daerah
        }

        // --- Laporan Daerah — agregasi dari Laporan Desa (§3.5), berhenti di FINAL (§3.1) ---
        foreach ([$periodeP2, $periodeP1] as $periode) {
            $this->buatLaporanDaerah($daerah, $periode, 1, $admin);
        }

        $this->command?->info('Data dummy Laporan Bulanan siap: 4 Kelompok, 2 Desa, 1 Daerah — periode '.$periodeP2.' s.d. '.$periodeP0.'.');
    }

    /** Hapus seluruh data yang dibuat seeder ini (lihat App\Console\Commands\ResetDummyLaporan). */
    public function hapusData(): void
    {
        LaporanBulanan::where(fn ($q) => $q->whereIn('kelompok_id', self::KELOMPOK_IDS)
            ->orWhereIn('desa_id', self::DESA_IDS)
            ->orWhere('daerah_id', self::DAERAH_ID))->delete();

        KegiatanPeserta::whereIn('kegiatan_id', $this->kegiatanScope()->pluck('id'))->delete();
        $this->kegiatanScope()->delete();

        ProgramMonitoringItem::whereIn('program_monitoring_id', ProgramMonitoring::whereIn('kelompok_id', self::KELOMPOK_IDS)->pluck('id'))->delete();
        ProgramMonitoring::whereIn('kelompok_id', self::KELOMPOK_IDS)->delete();

        MusyawarohItem::whereIn('musyawaroh_id', $this->musyawarohScope()->pluck('id'))->delete();
        $this->musyawarohScope()->delete();

        SensusSnapshot::whereIn('kelompok_id', self::KELOMPOK_IDS)->delete();

        Generus::withoutGlobalScopes()->whereIn('kelas_id', Kelas::whereIn('kelompok_id', self::KELOMPOK_IDS)->pluck('id'))->delete();
        Kelas::whereIn('kelompok_id', self::KELOMPOK_IDS)->forceDelete();

        User::whereIn('username', self::USERNAME_DUMMY)->delete();
    }

    private function kegiatanScope()
    {
        return Kegiatan::where('tingkat', 'KELOMPOK')
            ->where('penyelenggara_type', 'kelompok')
            ->whereIn('penyelenggara_id', self::KELOMPOK_IDS);
    }

    private function musyawarohScope()
    {
        return Musyawaroh::where(fn ($q) => $q->whereIn('kelompok_id', self::KELOMPOK_IDS)
            ->orWhere(fn ($q2) => $q2->where('tingkat', 'DESA')->whereIn('penyelenggara_id', self::DESA_IDS))
            ->orWhere(fn ($q2) => $q2->where('tingkat', 'DAERAH')->where('penyelenggara_id', self::DAERAH_ID)));
    }

    private function buatSiswa(Kelas $kelas, \Faker\Generator $faker, int $total, int $pendatang, int $usia): void
    {
        for ($i = 0; $i < $total; $i++) {
            $jenisKelamin = $i % 2 === 0 ? 'LAKI' : 'PEREMPUAN';
            $genderKey = $jenisKelamin === 'LAKI' ? 'male' : 'female';

            Generus::create([
                'nama' => $faker->firstName($genderKey).' '.$faker->lastName($genderKey),
                'tanggal_lahir' => Carbon::now()->subYears($usia)->subDays(random_int(0, 364)),
                'jenis_kelamin' => $jenisKelamin,
                'kelas_id' => $kelas->id,
                'jenjang_id' => $kelas->jenjang_id,
                'nama_orang_tua' => $faker->name(),
                'nomor_hp_orang_tua' => '08'.$faker->numerify('##########'),
                'status_domisili' => $i < $pendatang ? 'PENDATANG' : 'SETEMPAT',
                'status_aktif' => true,
            ]);
        }
    }

    private function provisionPjpKelompok(Kelompok $kelompok): User
    {
        $username = 'pjp.kelompok.'.\Illuminate\Support\Str::slug($kelompok->nama, '');

        $user = User::updateOrCreate(
            ['username' => $username],
            [
                'nama' => 'PJP Kelompok '.$kelompok->nama,
                'password' => 'password',
                'kelompok_id' => $kelompok->id,
                'desa_id' => null,
                'must_change_password' => true,
                'is_active' => true,
            ]
        );
        $user->syncRoles(['pjp-kelompok']);

        return $user;
    }

    private function provisionPjpDesa(Desa $desa): User
    {
        $username = 'pjp.desa.'.\Illuminate\Support\Str::slug($desa->nama, '');

        $user = User::updateOrCreate(
            ['username' => $username],
            [
                'nama' => 'PJP Desa '.$desa->nama,
                'password' => 'password',
                'kelompok_id' => null,
                'desa_id' => $desa->id,
                'must_change_password' => true,
                'is_active' => true,
            ]
        );
        $user->syncRoles(['pjp-desa']);

        return $user;
    }

    /** Kegiatan Tambahan + presensi, Program Monitoring, dan Musyawaroh tingkat Kelompok untuk satu periode. */
    private function isiDataSumber(Kelompok $kelompok, User $pjk, string $periode, \Faker\Generator $faker): void
    {
        $jenisTambahan = JenisKegiatan::where('nama', 'Tambahan')->value('id');
        $generusIds = Generus::withoutGlobalScopes()
            ->whereHas('kelas', fn ($q) => $q->where('kelompok_id', $kelompok->id))
            ->get(['id', 'kelas_id']);

        foreach ([['tgl' => '10', 'nama' => 'Pengajian Rutin'], ['tgl' => '24', 'nama' => 'Bakti Sosial']] as $i => $item) {
            $kegiatan = Kegiatan::create([
                'nama' => $item['nama'],
                'tingkat' => 'KELOMPOK',
                'penyelenggara_type' => 'kelompok',
                'penyelenggara_id' => $kelompok->id,
                'jenis_kegiatan_id' => $jenisTambahan,
                'tanggal' => "{$periode}-{$item['tgl']}",
                'status' => 'TERLAKSANA',
                'dibuat_oleh' => $pjk->id,
            ]);

            foreach ($generusIds->take(6) as $j => $generus) {
                $status = match (true) {
                    $j === 5 => 'ALPHA',
                    $j === 4 && $i === 0 => 'IZIN',
                    default => 'HADIR',
                };

                KegiatanPeserta::create([
                    'kegiatan_id' => $kegiatan->id,
                    'generus_id' => $generus->id,
                    'kelompok_id' => $kelompok->id,
                    'kelas_id' => $generus->kelas_id,
                    'status_presensi' => $status,
                ]);
            }
        }

        if (! ProgramMonitoring::where('kelompok_id', $kelompok->id)->exists()) {
            $program = ProgramMonitoring::create([
                'kelompok_id' => $kelompok->id, 'nama_program' => 'Turba GPN', 'status' => 'BERJALAN', 'dibuat_oleh' => $pjk->id,
            ]);
            ProgramMonitoringItem::create(['program_monitoring_id' => $program->id, 'status_item' => 'SELESAI', 'pic' => 'Guru GPN']);
            ProgramMonitoringItem::create(['program_monitoring_id' => $program->id, 'status_item' => 'PROSES', 'pic' => 'Guru GPN']);
            ProgramMonitoringItem::create(['program_monitoring_id' => $program->id, 'status_item' => 'BELUM']);
        }

        $mustin = JenisMusyawaroh::where('tingkat', 'KELOMPOK')->where('perlu_jumlah_hadir', true)->firstOrFail();
        Musyawaroh::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id, 'jenis_musyawaroh_id' => $mustin->id,
            'tanggal' => "{$periode}-05", 'jumlah_hadir' => random_int(8, 20),
        ]);

        $pengurus = JenisMusyawaroh::where('tingkat', 'KELOMPOK')->where('nama', 'Musyawaroh Pengurus KBM')->firstOrFail();
        $notulen = Musyawaroh::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id, 'jenis_musyawaroh_id' => $pengurus->id, 'tanggal' => "{$periode}-06",
        ]);
        MusyawarohItem::create(['musyawaroh_id' => $notulen->id, 'pokok_masalah' => 'Kehadiran '.$kelompok->nama.' periode '.$periode, 'keputusan' => 'Ditindaklanjuti Guru Kelas', 'pic' => 'Sekretaris KBM']);
    }

    private function isiMusyawarohDesa(Desa $desa, string $periode): void
    {
        $jenis = JenisMusyawaroh::where('tingkat', 'DESA')->firstOrFail();
        $notulen = Musyawaroh::create([
            'tingkat' => 'DESA', 'penyelenggara_type' => 'desa', 'penyelenggara_id' => $desa->id,
            'jenis_musyawaroh_id' => $jenis->id, 'tanggal' => "{$periode}-08",
        ]);
        MusyawarohItem::create(['musyawaroh_id' => $notulen->id, 'pokok_masalah' => 'Koordinasi laporan bulanan se-'.$desa->nama, 'pic' => 'PJP Desa']);
    }

    private function isiMusyawarohDaerah(Daerah $daerah, string $periode): void
    {
        $jenis = JenisMusyawaroh::where('tingkat', 'DAERAH')->firstOrFail();
        $notulen = Musyawaroh::create([
            'tingkat' => 'DAERAH', 'penyelenggara_type' => 'daerah', 'penyelenggara_id' => $daerah->id,
            'jenis_musyawaroh_id' => $jenis->id, 'tanggal' => "{$periode}-12",
        ]);
        MusyawarohItem::create(['musyawaroh_id' => $notulen->id, 'pokok_masalah' => 'Evaluasi laporan bulanan se-Daerah', 'pic' => 'Admin Daerah']);
    }

    private function buatLaporanKelompokFinal(Kelompok $kelompok, string $periode, int $versi, User $pjk): LaporanBulanan
    {
        $snapshot = app(SusunSnapshotLaporan::class)->susunKelompok($kelompok, $periode);

        return LaporanBulanan::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => $periode, 'versi' => $versi,
            'status' => 'FINAL', 'snapshot_data' => $snapshot,
            'dibuat_oleh' => $pjk->id, 'difinalisasi_oleh' => $pjk->id, 'difinalisasi_pada' => now(),
        ]);
    }

    private function buatLaporanDesa(Desa $desa, string $periode, int $versi, User $pjd): LaporanBulanan
    {
        $hasil = app(AgregasiLaporanDesa::class)->agregasi($desa, $periode);

        return LaporanBulanan::create([
            'desa_id' => $desa->id, 'tingkat' => 'DESA', 'periode' => $periode, 'versi' => $versi,
            'status' => 'FINAL', 'snapshot_data' => $hasil->snapshot,
            'dibuat_oleh' => $pjd->id, 'difinalisasi_oleh' => $pjd->id, 'difinalisasi_pada' => now(),
        ]);
    }

    private function buatLaporanDaerah(Daerah $daerah, string $periode, int $versi, User $admin): LaporanBulanan
    {
        $hasil = app(AgregasiLaporanDaerah::class)->agregasi($daerah, $periode);

        return LaporanBulanan::create([
            'daerah_id' => $daerah->id, 'tingkat' => 'DAERAH', 'periode' => $periode, 'versi' => $versi,
            'status' => 'FINAL', 'snapshot_data' => $hasil->snapshot,
            'dibuat_oleh' => $admin->id, 'difinalisasi_oleh' => $admin->id, 'difinalisasi_pada' => now(),
        ]);
    }
}
