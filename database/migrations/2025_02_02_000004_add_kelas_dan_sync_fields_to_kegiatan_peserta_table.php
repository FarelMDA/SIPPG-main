<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `kelas_id` didenormalisasi dari generus.kelas_id saat presensi dicatat — dasar
 * breakdown laporan per-kelas (docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5),
 * pola sama `kelompok_id` yang sudah ada di tabel ini. `client_uuid`/`field_updated_at`
 * untuk dukungan offline (idempotensi + konflik per-field), pola sama Presensi lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatan_peserta', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('generus_id')->constrained('kelas');
            $table->uuid('client_uuid')->nullable()->unique()->after('dicatat_oleh');
            $table->json('field_updated_at')->nullable()->after('client_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('kegiatan_peserta', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropColumn(['client_uuid', 'field_updated_at']);
            $table->dropConstrainedForeignId('kelas_id');
        });
    }
};
