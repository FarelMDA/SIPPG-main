<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Konversi musyawaroh.jenis (ENUM lama) jadi FK ke tabel master jenis_musyawaroh baru
// (2025_02_06_000000) — pola sama konversi kegiatan.jenis di
// 2025_02_01_000009_convert_kegiatan_jenis_to_jenis_kegiatan.php.
return new class extends Migration
{
    private const MAP = [
        'PENGURUS_KBM' => 'Musyawaroh Pengurus KBM',
        'LIMA_UNSUR' => 'Musyawaroh 5 Unsur',
        'PERTEMUAN_LIMA_UNSUR' => 'Pertemuan 5 Unsur',
        'MUSTIN_LUPG' => 'Mustin LUPG',
        'MUSYAWARAH_PPD_PPK' => 'Musyawarah PPD-PPK',
        'MUSYAWARAH_PPG_PPD' => 'Musyawarah PPG-PPD',
    ];

    public function up(): void
    {
        Schema::table('musyawaroh', function (Blueprint $table) {
            $table->foreignId('jenis_musyawaroh_id')->nullable()->after('jenis')->constrained('jenis_musyawaroh');
        });

        foreach (DB::table('musyawaroh')->get(['id', 'jenis']) as $row) {
            DB::table('musyawaroh')->where('id', $row->id)->update([
                'jenis_musyawaroh_id' => DB::table('jenis_musyawaroh')->where('nama', self::MAP[$row->jenis])->value('id'),
            ]);
        }

        Schema::table('musyawaroh', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });

        Schema::table('musyawaroh', function (Blueprint $table) {
            $table->foreignId('jenis_musyawaroh_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('musyawaroh', function (Blueprint $table) {
            $table->enum('jenis', array_keys(self::MAP))->nullable()->after('jenis_musyawaroh_id');
        });

        foreach (DB::table('musyawaroh')->get(['id', 'jenis_musyawaroh_id']) as $row) {
            $nama = DB::table('jenis_musyawaroh')->where('id', $row->jenis_musyawaroh_id)->value('nama');

            DB::table('musyawaroh')->where('id', $row->id)->update([
                'jenis' => array_search($nama, self::MAP, true) ?: null,
            ]);
        }

        Schema::table('musyawaroh', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jenis_musyawaroh_id');
            $table->enum('jenis', array_keys(self::MAP))->nullable(false)->change();
        });
    }
};
