<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generator Laporan Otomatis (SRS-Fase-2 §3, §10.2, UCIC-Fase-2 UC-31/UC-32) —
 * laporan Kelompok (kelompok_id terisi) atau agregasi Desa (desa_id terisi), tepat satu
 * dari keduanya sesuai `tingkat` — divalidasi aplikasi, tidak ada CHECK constraint DB
 * (pola sama seperti kolom lain di kodebase ini yang tidak bisa diwakili CHECK MySQL).
 * MySQL menganggap NULL berbeda-beda di unique index, jadi kedua UNIQUE di bawah tidak
 * saling bertabrakan meski salah satu kolomnya selalu NULL untuk tingkat lawannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_bulanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompok');
            $table->foreignId('desa_id')->nullable()->constrained('desa');
            $table->enum('tingkat', ['KELOMPOK', 'DESA']);
            $table->string('periode', 7); // 'YYYY-MM'
            $table->smallInteger('versi')->unsigned()->default(1);
            $table->enum('status', ['DRAFT', 'FINAL', 'DISETUJUI', 'REVISI_DIMINTA'])->default('DRAFT');
            $table->json('snapshot_data')->nullable();
            $table->text('catatan_revisi')->nullable();
            $table->foreignId('difinalisasi_oleh')->nullable()->constrained('users');
            $table->timestamp('difinalisasi_pada')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users');
            $table->timestamp('disetujui_pada')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();

            $table->unique(['kelompok_id', 'periode', 'versi'], 'uq_laporan_kelompok');
            $table->unique(['desa_id', 'periode', 'versi'], 'uq_laporan_desa');
            $table->index('status', 'idx_laporan_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_bulanan');
    }
};
