<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.4, §10
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_harian', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_uuid')->unique();
            $table->foreignId('kelas_id')->constrained('kelas');
            $table->date('tanggal');
            $table->enum('realisasi_status', ['SESUAI_JADWAL', 'TIDAK_TERLAKSANA', 'PENGGANTI']);
            $table->text('realisasi_catatan')->nullable();
            $table->text('catatan_guru')->nullable();
            $table->foreignId('dicatat_oleh')->constrained('users');
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users');
            $table->timestamp('disetujui_pada')->nullable();
            $table->timestamps();

            $table->unique(['kelas_id', 'tanggal'], 'uq_jurnal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_harian');
    }
};
