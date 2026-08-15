<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Restrukturisasi total kurikulum_kalender dari (jenjang, hari_ke urutan mengajar) jadi
 * (jenjang, tanggal_mulai, tanggal_selesai) — konvergensi Kurikulum-Kegiatan-Presensi,
 * lihat docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5. Belum ada data produksi
 * (Fase 1/2 belum deploy ke lapangan) sehingga drop & recreate langsung, tanpa backfill.
 * Hari libur TETAP tidak dimodelkan di sini — tabel `hari_libur` (dipakai bersama Kegiatan
 * Jadwal Berulang) yang jadi satu-satunya sumber kebenaran hari libur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('kurikulum_kalender');

        Schema::create('kurikulum_kalender', function (Blueprint $table) {
            $table->id();
            $table->string('jenjang', 20);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('jenis', ['MATERI', 'MUNAQOSAH'])->default('MATERI');
            $table->json('item_materi')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();

            $table->index(['jenjang', 'tanggal_mulai', 'tanggal_selesai'], 'idx_kurikulum_kalender_rentang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurikulum_kalender');

        Schema::create('kurikulum_kalender', function (Blueprint $table) {
            $table->id();
            $table->string('jenjang', 20);
            $table->smallInteger('hari_ke');
            $table->json('item_materi');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['jenjang', 'hari_ke'], 'uq_kalender');
        });
    }
};
