<?php

namespace Database\Seeders;

use App\Models\Kelompok;
use Illuminate\Database\Seeder;

/**
 * Data dari docs/dataset/kelompok_202607302308.csv (data Kelompok/KBM saat
 * ini). ID dipertahankan persis seperti sumber (id 6 memang tidak ada di CSV
 * asal — kemungkinan pernah dihapus di sistem sumber).
 */
class KelompokSeeder extends Seeder
{
    private const DATA = [
        ['id' => 1, 'nama' => 'BIG 1', 'desa_id' => 1],
        ['id' => 2, 'nama' => 'BIG 2', 'desa_id' => 1],
        ['id' => 3, 'nama' => 'Cakra', 'desa_id' => 1],
        ['id' => 4, 'nama' => 'Limo', 'desa_id' => 1],
        ['id' => 5, 'nama' => 'Maruyung', 'desa_id' => 1],
        ['id' => 7, 'nama' => 'Bintaro Jaya Barat', 'desa_id' => 2],
        ['id' => 8, 'nama' => 'Bintaro Jaya Timur', 'desa_id' => 2],
        ['id' => 9, 'nama' => 'Bintaro Jaya Utama', 'desa_id' => 2],
        ['id' => 10, 'nama' => "Bintaro Tis'wah Wa Chomsah 1", 'desa_id' => 2],
        ['id' => 11, 'nama' => "Bintaro Tis'wah Wa Chomsah 2", 'desa_id' => 2],
        ['id' => 12, 'nama' => 'Pondok Ranji Timur', 'desa_id' => 2],
        ['id' => 13, 'nama' => 'Pondok Ranji Barat', 'desa_id' => 2],
        ['id' => 14, 'nama' => 'Pondok Jaya', 'desa_id' => 2],
        ['id' => 15, 'nama' => 'Gandul Utara', 'desa_id' => 3],
        ['id' => 16, 'nama' => 'Gandul Selatan', 'desa_id' => 3],
        ['id' => 17, 'nama' => 'Cipedak', 'desa_id' => 3],
        ['id' => 18, 'nama' => 'Ciganjur', 'desa_id' => 3],
        ['id' => 19, 'nama' => 'Cinere', 'desa_id' => 3],
        ['id' => 20, 'nama' => 'Antena', 'desa_id' => 4],
        ['id' => 21, 'nama' => 'Bungur', 'desa_id' => 4],
        ['id' => 22, 'nama' => 'Cipete Selatan', 'desa_id' => 4],
        ['id' => 23, 'nama' => 'Karya Utama', 'desa_id' => 4],
        ['id' => 24, 'nama' => 'Kramat Batu', 'desa_id' => 4],
        ['id' => 25, 'nama' => 'Praja', 'desa_id' => 4],
        ['id' => 26, 'nama' => 'Radio Dalam', 'desa_id' => 4],
        ['id' => 27, 'nama' => 'Sanggar', 'desa_id' => 4],
        ['id' => 28, 'nama' => 'Senayan', 'desa_id' => 5],
        ['id' => 29, 'nama' => 'Kemandoran', 'desa_id' => 5],
        ['id' => 30, 'nama' => 'SBI', 'desa_id' => 5],
        ['id' => 31, 'nama' => 'Cidodol', 'desa_id' => 5],
        ['id' => 32, 'nama' => 'Gunung', 'desa_id' => 5],
        ['id' => 33, 'nama' => 'Sriwijaya', 'desa_id' => 5],
        ['id' => 34, 'nama' => 'Poncol', 'desa_id' => 5],
        ['id' => 35, 'nama' => 'PIU', 'desa_id' => 6],
        ['id' => 36, 'nama' => 'PIS', 'desa_id' => 6],
        ['id' => 37, 'nama' => 'Mawar 1', 'desa_id' => 6],
        ['id' => 38, 'nama' => 'Mawar 2', 'desa_id' => 6],
        ['id' => 39, 'nama' => 'Rempoa', 'desa_id' => 6],
        ['id' => 40, 'nama' => 'Bulak Indah', 'desa_id' => 7],
        ['id' => 41, 'nama' => 'Wadasari', 'desa_id' => 7],
        ['id' => 42, 'nama' => 'Delok', 'desa_id' => 7],
        ['id' => 43, 'nama' => 'Binut', 'desa_id' => 7],
        ['id' => 44, 'nama' => 'Binsel', 'desa_id' => 7],
        ['id' => 45, 'nama' => 'Bintim', 'desa_id' => 7],
    ];

    public function run(): void
    {
        foreach (self::DATA as $row) {
            Kelompok::updateOrCreate(
                ['id' => $row['id']],
                ['desa_id' => $row['desa_id'], 'nama' => $row['nama'], 'abbreviation' => $row['nama']]
            );
        }
    }
}
