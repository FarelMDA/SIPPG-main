<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS-Fase-2 §2.8, §10.1 — label pengelompokan lintas-tingkat untuk rekap gabungan (UC-40).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_program', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('tingkat_tertinggi', ['KELOMPOK', 'DESA', 'DAERAH']);
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_program');
    }
};
