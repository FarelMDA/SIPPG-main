<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS-Fase-2 §2.5, §10.1 — perluasan `kegiatan` agar kejadian hasil generate Jadwal
// Rutin (dan juga Insidental, field-field ini berlaku untuk keduanya) tercatat asal
// & atributnya. Unique (kegiatan_jadwal_id, tanggal, sesi_ke) = kunci idempotent
// generate/regenerasi (UC-30), pola sama client_uuid Presensi Harian (SRS-Fase-1 §13.2).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->text('deskripsi')->nullable()->after('nama');
            $table->foreignId('kegiatan_jadwal_id')->nullable()->after('tingkat')->constrained('kegiatan_jadwal');
            $table->smallInteger('sesi_ke')->unsigned()->default(1)->after('tanggal');
            $table->enum('target_tipe', ['SEMUA', 'JENJANG_KELAS', 'INDIVIDU'])->default('SEMUA')->after('jenis');
            $table->foreignId('kegiatan_program_id')->nullable()->after('target_tipe')->constrained('kegiatan_program');
            $table->text('catatan_status')->nullable()->after('status');

            $table->unique(['kegiatan_jadwal_id', 'tanggal', 'sesi_ke'], 'uq_kegiatan_jadwal_tanggal_sesi');
        });
    }

    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropUnique('uq_kegiatan_jadwal_tanggal_sesi');
            $table->dropConstrainedForeignId('kegiatan_jadwal_id');
            $table->dropConstrainedForeignId('kegiatan_program_id');
            $table->dropColumn(['deskripsi', 'sesi_ke', 'target_tipe', 'catatan_status']);
        });
    }
};
