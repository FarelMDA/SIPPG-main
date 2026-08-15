<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.4, §9 — client_uuid = kunci idempotent sync offline (§13).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_uuid')->unique();
            $table->foreignId('kelas_id')->constrained('kelas');
            $table->foreignId('generus_id')->constrained('generus');
            $table->date('tanggal');
            $table->enum('status', ['HADIR', 'IZIN', 'SAKIT', 'ALPHA']);
            $table->foreignId('dicatat_oleh')->constrained('users');
            $table->timestamps();

            $table->unique(['kelas_id', 'generus_id', 'tanggal'], 'uq_presensi');
            $table->index('generus_id', 'idx_presensi_generus');
            $table->index('tanggal', 'idx_presensi_tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi');
    }
};
