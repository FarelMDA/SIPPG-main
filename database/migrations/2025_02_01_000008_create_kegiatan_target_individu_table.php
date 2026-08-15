<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS-Fase-2 §2.4, §10.1 — penargetan peserta by Generus individu untuk Kegiatan
// (Insidental maupun kejadian hasil generate Jadwal — disalin/snapshot).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_target_individu', function (Blueprint $table) {
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('generus_id')->constrained('generus');

            $table->primary(['kegiatan_id', 'generus_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_target_individu');
    }
};
