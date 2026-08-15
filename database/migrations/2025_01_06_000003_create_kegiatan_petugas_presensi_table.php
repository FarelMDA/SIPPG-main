<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.8, §18.2 — tepat satu dari user_id/generus_id terisi (divalidasi di aplikasi).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_petugas_presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('kelompok_id')->constrained('kelompok');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('generus_id')->nullable()->constrained('generus');
            $table->uuid('token')->nullable()->unique();
            $table->timestamp('token_kedaluwarsa')->nullable();
            $table->foreignId('ditugaskan_oleh')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['kegiatan_id', 'kelompok_id', 'user_id', 'generus_id'], 'uq_kegiatan_petugas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_petugas_presensi');
    }
};
