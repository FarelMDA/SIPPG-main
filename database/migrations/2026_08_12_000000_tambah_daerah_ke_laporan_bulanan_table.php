<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Agregasi Laporan Daerah (SRS-Fase-2 §3.5, UCIC-Fase-2 UC-32) — permintaan fitur baru,
// dimajukan dari rencana awal Fase 4. Pola sama seperti expand_musyawaroh_multi_tingkat:
// tambah daerah_id + tingkat DAERAH ke tabel yang sudah ada, bukan tabel baru.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_bulanan', function (Blueprint $table) {
            $table->foreignId('daerah_id')->nullable()->after('desa_id')->constrained('daerah');
            $table->enum('tingkat', ['KELOMPOK', 'DESA', 'DAERAH'])->change();
            $table->unique(['daerah_id', 'periode', 'versi'], 'uq_laporan_daerah');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_bulanan', function (Blueprint $table) {
            $table->dropUnique('uq_laporan_daerah');
            $table->dropConstrainedForeignId('daerah_id');
            $table->enum('tingkat', ['KELOMPOK', 'DESA'])->change();
        });
    }
};
