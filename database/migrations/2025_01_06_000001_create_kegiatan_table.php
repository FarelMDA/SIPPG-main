<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.8, §18.1 — penyelenggara_id polimorfik (kelompok/desa/daerah), tanpa FK
// constraint DB karena tabel targetnya berbeda tergantung `tingkat`.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('tingkat', ['KELOMPOK', 'DESA', 'DAERAH']);
            $table->enum('penyelenggara_type', ['kelompok', 'desa', 'daerah']);
            $table->unsignedBigInteger('penyelenggara_id');
            $table->enum('jenis', ['TAMBAHAN', 'PENGUATAN', 'PROGRAM_KHUSUS', 'EKSTRAKURIKULER']);
            $table->date('tanggal');
            $table->string('tempat')->nullable();
            $table->enum('status', ['TERJADWAL', 'TERLAKSANA', 'TIDAK_TERLAKSANA'])->default('TERJADWAL');
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();

            $table->index(['penyelenggara_type', 'penyelenggara_id'], 'idx_kegiatan_penyelenggara');
            $table->index('tanggal', 'idx_kegiatan_tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};
