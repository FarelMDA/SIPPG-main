<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retire Presensi + Jurnal Harian (KBM reguler harian) — digantikan oleh Kegiatan
 * ber-`kurikulum_kalender_id` (lihat migration 2025_02_02_000003 & App\Services\Kegiatan\
 * GeneratorKegiatanDariJadwal). Aman drop langsung: belum ada Fase yang deploy ke lapangan,
 * tidak ada data produksi. down() merekonstruksi skema asli persis untuk reversibilitas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('jurnal_harian');
        Schema::dropIfExists('presensi');
    }

    public function down(): void
    {
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_uuid')->unique();
            $table->foreignId('kelas_id')->constrained('kelas');
            $table->foreignId('generus_id')->constrained('generus');
            $table->date('tanggal');
            $table->enum('status', ['HADIR', 'IZIN', 'SAKIT', 'ALPHA']);
            $table->foreignId('dicatat_oleh')->constrained('users');
            $table->json('field_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['kelas_id', 'generus_id', 'tanggal'], 'uq_presensi');
            $table->index('generus_id', 'idx_presensi_generus');
            $table->index('tanggal', 'idx_presensi_tanggal');
        });

        Schema::create('jurnal_harian', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_uuid')->unique();
            $table->foreignId('kelas_id')->constrained('kelas');
            $table->date('tanggal');
            $table->enum('realisasi_status', ['SESUAI_JADWAL', 'TIDAK_TERLAKSANA', 'PENGGANTI']);
            $table->text('realisasi_catatan')->nullable();
            $table->text('catatan_guru')->nullable();
            $table->foreignId('dicatat_oleh')->constrained('users');
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users');
            $table->timestamp('disetujui_pada')->nullable();
            $table->json('field_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['kelas_id', 'tanggal'], 'uq_jurnal');
        });
    }
};
