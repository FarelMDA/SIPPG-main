<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.1 — enum jenjang 15 opsi (PAUD s.d. GPN-B), lihat SRS §6.1/§4.1.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_id')->constrained('kelompok');
            $table->string('nama', 100);
            $table->enum('jenjang', [
                'PAUD',
                'DASAR_1', 'DASAR_2', 'DASAR_3', 'DASAR_4', 'DASAR_5', 'DASAR_6',
                'MENENGAH_7', 'MENENGAH_8', 'MENENGAH_9',
                'LANJUTAN_10', 'LANJUTAN_11', 'LANJUTAN_12',
                'GPN_A', 'GPN_B',
            ]);
            $table->timestamps();

            $table->index('kelompok_id', 'idx_kelas_kelompok');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
