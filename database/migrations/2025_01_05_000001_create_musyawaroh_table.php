<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.5, §11 — versi dasar tanpa carry-over otomatis (Fase 2).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('musyawaroh', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_id')->constrained('kelompok');
            $table->enum('jenis', ['PENGURUS_KBM', 'LIMA_UNSUR', 'PERTEMUAN_LIMA_UNSUR', 'MUSTIN_LUPG']);
            $table->date('tanggal');
            $table->integer('jumlah_hadir')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('kelompok_id', 'idx_musyawaroh_kelompok');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('musyawaroh');
    }
};
