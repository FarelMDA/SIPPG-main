<?php

namespace Database\Seeders;

use App\Models\Desa;
use Illuminate\Database\Seeder;

/**
 * Data dari docs/dataset/desa_202607302308.csv (data Desa saat ini, semua
 * berada di bawah Daerah id 1). ID dipertahankan persis seperti sumber agar
 * referensi `parentId` di kelompok_202607302308.csv (KelompokSeeder) tetap valid.
 */
class DesaSeeder extends Seeder
{
    private const DATA = [
        ['id' => 1, 'nama' => 'Bumi Indah Grogol'],
        ['id' => 2, 'nama' => 'Bintaro Jaya'],
        ['id' => 3, 'nama' => 'Cinere'],
        ['id' => 4, 'nama' => 'Gandaria'],
        ['id' => 5, 'nama' => 'Kebayoran'],
        ['id' => 6, 'nama' => 'Pondok Indah'],
        ['id' => 7, 'nama' => 'Pesanggrahan'],
    ];

    public function run(): void
    {
        foreach (self::DATA as $row) {
            Desa::updateOrCreate(
                ['id' => $row['id']],
                ['daerah_id' => 1, 'nama' => $row['nama'], 'abbreviation' => $row['nama']]
            );
        }
    }
}
