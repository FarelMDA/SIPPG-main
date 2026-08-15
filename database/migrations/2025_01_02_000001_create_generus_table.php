<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.2 — kelas_id selalu wajib (PRD §8), berlaku sama untuk Setempat/Perantauan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generus', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['LAKI', 'PEREMPUAN']);
            $table->foreignId('kelas_id')->constrained('kelas');
            $table->string('nama_orang_tua');
            $table->string('nomor_hp_orang_tua', 20);
            $table->enum('status_domisili', ['SETEMPAT', 'PERANTAUAN'])->default('SETEMPAT');
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();

            $table->index('kelas_id', 'idx_generus_kelas');
            $table->index('nomor_hp_orang_tua', 'idx_generus_nomor_hp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generus');
    }
};
