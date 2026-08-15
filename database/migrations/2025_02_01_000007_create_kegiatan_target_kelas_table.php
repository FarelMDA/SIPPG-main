<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS-Fase-2 §2.4, §10.1 — penargetan peserta by Kelas untuk Kegiatan (Insidental
// maupun kejadian hasil generate Jadwal — disalin/snapshot dari kegiatan_jadwal_target_kelas).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_target_kelas', function (Blueprint $table) {
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas');

            $table->primary(['kegiatan_id', 'kelas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_target_kelas');
    }
};
