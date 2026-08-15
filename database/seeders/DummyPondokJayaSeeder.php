<?php

namespace Database\Seeders;

use App\Models\AkunOrangTua;
use App\Models\Generus;
use App\Models\Jenjang;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\Pendidik;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data dummy Siswa (Generus), Guru (Pendidik), akun Portal Orang Tua, dan akun
 * PJP Desa/Kelompok terkait untuk Desa "Bintaro Jaya" & Kelompok "Pondok Jaya"
 * — dipakai untuk demo/uji coba tampilan (sensus, dashboard, portal orang tua,
 * login PJP, dsb), BUKAN bagian dari seeding baseline. Jalankan manual:
 *   php artisan db:seed --class=DummyPondokJayaSeeder
 *
 * Idempotent: aman dijalankan berulang — Kelas/Pendidik/User di-updateOrCreate,
 * Generus tidak dibuat ulang jika jumlah per kelas sudah sesuai target, dan
 * provisioning akun Orang Tua men-dedup nomor HP seperti alur asli (§12.2).
 */
class DummyPondokJayaSeeder extends Seeder
{
    // Jenjang yang dipakai (kode) — nama Kelas kini turunan Jenjang.label, tidak diketik manual.
    private const KELAS = [
        'PAUD_A', 'PAUD_B',
        'DASAR_1', 'DASAR_2', 'DASAR_3', 'DASAR_4', 'DASAR_5', 'DASAR_6',
        'MENENGAH_7', 'MENENGAH_8', 'MENENGAH_9',
        'LANJUTAN_10', 'LANJUTAN_11', 'LANJUTAN_12',
        'GPN_A', 'GPN_B',
    ];

    // Usia representatif per jenjang (perkiraan dari bracket usia SRS/PRD §4) — dipakai untuk tanggal_lahir dummy.
    private const USIA_TAHUN = [
        'PAUD_A' => 3, 'PAUD_B' => 4,
        'DASAR_1' => 6, 'DASAR_2' => 7, 'DASAR_3' => 8, 'DASAR_4' => 9, 'DASAR_5' => 10, 'DASAR_6' => 11,
        'MENENGAH_7' => 12, 'MENENGAH_8' => 13, 'MENENGAH_9' => 14,
        'LANJUTAN_10' => 15, 'LANJUTAN_11' => 16, 'LANJUTAN_12' => 17,
        'GPN_A' => 20, 'GPN_B' => 26,
    ];

    private const PENDIDIK = [
        'Ibu Ita' => ['jenis' => 'MT', 'kelas' => ['PAUD_A', 'PAUD_B', 'MENENGAH_7', 'MENENGAH_8', 'MENENGAH_9', 'LANJUTAN_10', 'LANJUTAN_11', 'GPN_A', 'GPN_B']],
        'Ibu Senja' => ['jenis' => 'MS', 'kelas' => ['DASAR_1', 'DASAR_2']],
        'Ibu Syifa' => ['jenis' => 'MT', 'kelas' => ['DASAR_3', 'DASAR_4', 'MENENGAH_7', 'MENENGAH_8', 'MENENGAH_9', 'LANJUTAN_10', 'LANJUTAN_11', 'GPN_A', 'GPN_B']],
        'Bp Irfan' => ['jenis' => 'MT', 'kelas' => ['DASAR_5', 'DASAR_6', 'GPN_A', 'GPN_B']],
    ];

    // 41 siswa PAUD A s.d. Kelas 12, semua Setempat — dipecah rata per 14 kelas (13x3 + 1x2).
    private const JUMLAH_PER_KELAS_ACR = [
        'PAUD_A' => 3, 'PAUD_B' => 3,
        'DASAR_1' => 3, 'DASAR_2' => 3, 'DASAR_3' => 3, 'DASAR_4' => 3, 'DASAR_5' => 3, 'DASAR_6' => 3,
        'MENENGAH_7' => 3, 'MENENGAH_8' => 3, 'MENENGAH_9' => 3,
        'LANJUTAN_10' => 3, 'LANJUTAN_11' => 3, 'LANJUTAN_12' => 2,
    ];

    private int $genderCounter = 0;

    public function run(): void
    {
        $kelompok = Kelompok::where('nama', 'Pondok Jaya')->firstOrFail();
        $faker = fake('id_ID');

        $jenjangIds = Jenjang::pluck('id', 'kode');

        $kelas = collect(self::KELAS)->mapWithKeys(fn ($jenjang) => [
            $jenjang => Kelas::updateOrCreate(
                ['kelompok_id' => $kelompok->id, 'jenjang_id' => $jenjangIds[$jenjang]],
                ['status_aktif' => true]
            ),
        ]);

        foreach (self::PENDIDIK as $nama => $data) {
            $pendidik = Pendidik::updateOrCreate(
                ['kelompok_id' => $kelompok->id, 'nama' => $nama],
                ['jenis' => $data['jenis']]
            );

            $pendidik->kelas()->sync($kelas->only($data['kelas'])->pluck('id'));
        }

        foreach (self::JUMLAH_PER_KELAS_ACR as $jenjang => $jumlah) {
            $this->buatSiswa($kelas[$jenjang], $jenjang, $faker, total: $jumlah, pendatang: 0);
        }

        $this->buatSiswa($kelas['GPN_A'], 'GPN_A', $faker, total: 15, pendatang: 7);
        $this->buatSiswa($kelas['GPN_B'], 'GPN_B', $faker, total: 46, pendatang: 29);

        $this->provisionAkunOrangTua($kelas->pluck('id'));
        $this->provisionAkunPjp($kelompok);
    }

    /** Akun PJP Desa (Bintaro Jaya) & PJP Kelompok (Pondok Jaya) untuk demo login — password tetap 'password', wajib ganti di login pertama (sama seperti akun admin bawaan). */
    private function provisionAkunPjp(Kelompok $kelompok): void
    {
        $desa = $kelompok->desa;

        $pjpDesa = User::updateOrCreate(
            ['username' => 'pjp.desa.bintarojaya'],
            [
                'nama' => 'PJP Desa '.$desa->nama,
                'password' => 'password',
                'kelompok_id' => null,
                'desa_id' => $desa->id,
                'must_change_password' => true,
                'is_active' => true,
            ]
        );
        $pjpDesa->syncRoles(['pjp-desa']);

        $pjpKelompok = User::updateOrCreate(
            ['username' => 'pjp.kelompok.pondokjaya'],
            [
                'nama' => 'PJP Kelompok '.$kelompok->nama,
                'password' => 'password',
                'kelompok_id' => $kelompok->id,
                'desa_id' => null,
                'must_change_password' => true,
                'is_active' => true,
            ]
        );
        $pjpKelompok->syncRoles(['pjp-kelompok']);
    }

    private function buatSiswa(Kelas $kelas, string $jenjang, \Faker\Generator $faker, int $total, int $pendatang): void
    {
        $sudahAda = $kelas->generus()->count();

        if ($sudahAda >= $total) {
            return;
        }

        $usia = self::USIA_TAHUN[$jenjang];

        for ($i = $sudahAda; $i < $total; $i++) {
            $jenisKelamin = $this->genderCounter % 2 === 0 ? 'LAKI' : 'PEREMPUAN';
            $this->genderCounter++;

            $genderKey = $jenisKelamin === 'LAKI' ? 'male' : 'female';

            Generus::create([
                // Nama siswa tanpa gelar akademik — beda dari nama_orang_tua di bawah,
                // yang boleh pakai $faker->name() (bisa menyertakan gelar seperti "S.Pd").
                'nama' => $faker->firstName($genderKey).' '.$faker->lastName($genderKey),
                'tanggal_lahir' => Carbon::now()->subYears($usia)->subDays(random_int(0, 364)),
                'jenis_kelamin' => $jenisKelamin,
                'kelas_id' => $kelas->id,
                'nama_orang_tua' => $faker->name(),
                'nomor_hp_orang_tua' => '08'.$faker->numerify('##########'),
                'status_domisili' => $i < $pendatang ? 'PENDATANG' : 'SETEMPAT',
                'status_aktif' => true,
            ]);
        }
    }

    /** Provisioning akun Portal Orang Tua per Generus, meniru ProvisioningAkunOrangTua (dedup by nomor_hp_hash, §12.2). */
    private function provisionAkunOrangTua($kelasIds): void
    {
        Generus::withoutGlobalScopes()
            ->whereIn('kelas_id', $kelasIds)
            ->doesntHave('akunOrangTua')
            ->each(function (Generus $generus) {
                $nomorHp = $generus->nomor_hp_orang_tua;

                $akun = AkunOrangTua::where('nomor_hp_hash', AkunOrangTua::hashNomorHp($nomorHp))->first();

                if (! $akun) {
                    $akun = AkunOrangTua::create([
                        'nomor_hp' => $nomorHp,
                        'password' => Hash::make(Str::password(8, symbols: false)),
                        'must_change_password' => true,
                        'is_active' => true,
                    ]);
                }

                $akun->generus()->syncWithoutDetaching([$generus->id]);
            });
    }
}
