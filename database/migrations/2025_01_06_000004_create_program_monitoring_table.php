<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.8, §18.3 — generik, nama_program bebas teks (bukan enum tetap).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_monitoring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_id')->constrained('kelompok');
            $table->string('nama_program');
            $table->text('target_peserta')->nullable();
            $table->date('tenggat')->nullable();
            $table->enum('status', ['BELUM_MULAI', 'BERJALAN', 'SELESAI'])->default('BELUM_MULAI');
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();

            $table->index('kelompok_id', 'idx_program_monitoring_kelompok');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_monitoring');
    }
};
