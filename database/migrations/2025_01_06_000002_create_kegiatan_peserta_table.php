<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.8, §18.1 — kelompok_id denormalized untuk mempercepat agregasi rekap.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_peserta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('generus_id')->constrained('generus');
            $table->foreignId('kelompok_id')->constrained('kelompok');
            $table->enum('status_presensi', ['HADIR', 'IZIN', 'SAKIT', 'ALPHA'])->default('HADIR');
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['kegiatan_id', 'generus_id'], 'uq_kegiatan_peserta');
            $table->index('kelompok_id', 'idx_kegiatan_peserta_kelompok');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_peserta');
    }
};
