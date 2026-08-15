<?php

namespace Database\Factories;

use App\Models\JenisKegiatan;
use App\Models\KegiatanJadwal;
use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KegiatanJadwalFactory extends Factory
{
    protected $model = KegiatanJadwal::class;

    public function definition(): array
    {
        $kelompok = Kelompok::factory();

        return [
            'nama' => 'Pengajian '.$this->faker->word(),
            'deskripsi' => null,
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok,
            'jenis_kegiatan_id' => fn () => JenisKegiatan::query()->inRandomOrder()->value('id') ?? JenisKegiatan::factory(),
            'frekuensi_tipe' => 'HARIAN',
            'hari_dalam_minggu' => ['SABTU'],
            'minggu_ke_dalam_bulan' => null,
            'interval_minggu' => null,
            'jumlah_sesi_per_kemunculan' => 1,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(3)->toDateString(),
            'tempat' => 'Kediaman Ketua Kelompok',
            'rotasi_tempat' => null,
            'target_tipe' => 'SEMUA',
            'kegiatan_program_id' => null,
            'status' => 'AKTIF',
            'dibuat_oleh' => User::factory(),
        ];
    }
}
