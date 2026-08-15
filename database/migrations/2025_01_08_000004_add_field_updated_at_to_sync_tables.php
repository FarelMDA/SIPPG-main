<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UC-14 / SRS §13.3 — resolusi konflik sync offline harus last-write-wins PER FIELD,
 * bukan per baris utuh. `updated_at` bawaan Eloquent cuma satu nilai untuk seluruh
 * baris, tidak cukup granular untuk itu — kolom `field_updated_at` (JSON map
 * nama_kolom => timestamp klien saat kolom itu terakhir disinkronkan) menyimpan
 * histori per-kolom yang dibutuhkan SyncController untuk membandingkan field
 * mana yang lebih baru, independen dari field lain di baris yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            $table->json('field_updated_at')->nullable()->after('status');
        });

        Schema::table('jurnal_harian', function (Blueprint $table) {
            $table->json('field_updated_at')->nullable()->after('catatan_guru');
        });
    }

    public function down(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            $table->dropColumn('field_updated_at');
        });

        Schema::table('jurnal_harian', function (Blueprint $table) {
            $table->dropColumn('field_updated_at');
        });
    }
};
