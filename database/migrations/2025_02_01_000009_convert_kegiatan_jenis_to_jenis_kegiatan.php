<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Konversi kegiatan.jenis (ENUM lama, sudah committed sejak Fase 1) jadi FK ke
// tabel master jenis_kegiatan baru (2025_02_01_000000) — pola sama konversi
// kelas.jenjang di 2025_01_07_000004_create_jenjang_table.php.
return new class extends Migration
{
    private const MAP = [
        'TAMBAHAN' => 'Tambahan',
        'PENGUATAN' => 'Penguatan',
        'PROGRAM_KHUSUS' => 'Program Khusus',
        'EKSTRAKURIKULER' => 'Ekstrakurikuler',
    ];

    public function up(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->foreignId('jenis_kegiatan_id')->nullable()->after('jenis')->constrained('jenis_kegiatan');
        });

        foreach (DB::table('kegiatan')->get(['id', 'jenis']) as $row) {
            DB::table('kegiatan')->where('id', $row->id)->update([
                'jenis_kegiatan_id' => DB::table('jenis_kegiatan')->where('nama', self::MAP[$row->jenis])->value('id'),
            ]);
        }

        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });

        Schema::table('kegiatan', function (Blueprint $table) {
            $table->foreignId('jenis_kegiatan_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->enum('jenis', array_keys(self::MAP))->nullable()->after('jenis_kegiatan_id');
        });

        foreach (DB::table('kegiatan')->get(['id', 'jenis_kegiatan_id']) as $row) {
            $nama = DB::table('jenis_kegiatan')->where('id', $row->jenis_kegiatan_id)->value('nama');

            DB::table('kegiatan')->where('id', $row->id)->update([
                'jenis' => array_search($nama, self::MAP, true) ?: null,
            ]);
        }

        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jenis_kegiatan_id');
            $table->enum('jenis', array_keys(self::MAP))->nullable(false)->change();
        });
    }
};
