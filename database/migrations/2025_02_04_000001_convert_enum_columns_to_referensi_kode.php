<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah kolom ENUM yang nilainya sekarang bersumber dari tabel referensi baru
 * (jenis_kelamin, status_domisili, jenis_pendidik, status_presensi) jadi VARCHAR
 * soft-reference (kode) — pola sama kurikulum_kalender.jenjang. Nilai data yang
 * sudah ada tidak berubah (kode ENUM lama == kode baris referensi baru).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generus', function (Blueprint $table) {
            $table->string('jenis_kelamin', 20)->change();
            $table->string('status_domisili', 20)->default('SETEMPAT')->change();
        });

        Schema::table('sensus_snapshots', function (Blueprint $table) {
            $table->string('jenis_kelamin', 20)->change();
            $table->string('status_domisili', 20)->change();
        });

        Schema::table('generus_status_histories', function (Blueprint $table) {
            $table->string('status_domisili', 20)->change();
        });

        Schema::table('pendidik', function (Blueprint $table) {
            $table->string('jenis', 20)->change();
        });

        Schema::table('kegiatan_peserta', function (Blueprint $table) {
            $table->string('status_presensi', 20)->default('HADIR')->change();
        });
    }

    public function down(): void
    {
        Schema::table('generus', function (Blueprint $table) {
            $table->enum('jenis_kelamin', ['LAKI', 'PEREMPUAN'])->change();
            $table->enum('status_domisili', ['SETEMPAT', 'PENDATANG'])->default('SETEMPAT')->change();
        });

        Schema::table('sensus_snapshots', function (Blueprint $table) {
            $table->enum('jenis_kelamin', ['LAKI', 'PEREMPUAN'])->change();
            $table->enum('status_domisili', ['SETEMPAT', 'PENDATANG'])->change();
        });

        Schema::table('generus_status_histories', function (Blueprint $table) {
            $table->enum('status_domisili', ['SETEMPAT', 'PENDATANG'])->change();
        });

        Schema::table('pendidik', function (Blueprint $table) {
            $table->enum('jenis', ['MT', 'MS'])->change();
        });

        Schema::table('kegiatan_peserta', function (Blueprint $table) {
            $table->enum('status_presensi', ['HADIR', 'IZIN', 'SAKIT', 'ALPHA'])->default('HADIR')->change();
        });
    }
};
