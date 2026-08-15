<?php

namespace App\Console\Commands;

use Database\Seeders\DummyLaporanBulananSeeder;
use Illuminate\Console\Command;

/**
 * Hapus seluruh data dummy yang dibuat DummyLaporanBulananSeeder (4 Kelompok, 2 Desa,
 * 1 Daerah — lihat docblock seeder itu untuk scope persisnya), tanpa membangunnya ulang.
 * Untuk mengisi lagi setelah reset: `php artisan db:seed --class=DummyLaporanBulananSeeder`.
 */
class ResetDummyLaporan extends Command
{
    protected $signature = 'dummy:laporan-reset';

    protected $description = 'Hapus data dummy Laporan Bulanan (lihat DummyLaporanBulananSeeder)';

    public function handle(): int
    {
        if (! $this->confirm('Hapus seluruh data dummy Laporan Bulanan (Kelompok/Desa/Daerah dummy, laporan, kegiatan, musyawaroh, sensus terkait)?')) {
            $this->comment('Dibatalkan.');

            return self::SUCCESS;
        }

        app(DummyLaporanBulananSeeder::class)->hapusData();

        $this->info('Data dummy Laporan Bulanan sudah dihapus. Jalankan `php artisan db:seed --class=DummyLaporanBulananSeeder` untuk mengisi ulang.');

        return self::SUCCESS;
    }
}
