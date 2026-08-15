<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS-Fase-2 §2.4, §10.1 — penargetan peserta by Kelas untuk Jadwal Kegiatan Berulang.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_jadwal_target_kelas', function (Blueprint $table) {
            $table->foreignId('kegiatan_jadwal_id')->constrained('kegiatan_jadwal')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas');

            $table->primary(['kegiatan_jadwal_id', 'kelas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_jadwal_target_kelas');
    }
};
