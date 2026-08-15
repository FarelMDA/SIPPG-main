<?php

namespace Database\Seeders;

use App\Models\Daerah;
use Illuminate\Database\Seeder;

// Satu baris konfigurasi Daerah (PRD §18.4) — tidak multi-tenant, SRS §1.1.
// Data dari docs/dataset/daerah_202607302308.csv (data Daerah saat ini).
class DaerahSeeder extends Seeder
{
    public function run(): void
    {
        Daerah::updateOrCreate(
            ['id' => 1],
            ['nama' => 'Jakarta Selatan 1', 'abbreviation' => 'Jakarta Selatan 1']
        );
    }
}
