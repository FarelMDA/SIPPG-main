<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Jenjang jadi tabel master data sendiri, gantikan ENUM di kelas.jenjang —
// menambah/mengubah jenjang ke depan cukup ubah baris data, tanpa migration
// schema (sudah 2x butuh ALTER TABLE ENUM: restrukturisasi Menengah/Lanjutan,
// lalu split PAUD_A/PAUD_B). `kurikulum_kalender.jenjang` & `sensus_snapshots.jenjang`
// SENGAJA tetap kolom string (soft-reference ke jenjang.kode) — keduanya sudah
// VARCHAR bukan ENUM, jadi tidak pernah butuh migration schema saat jenjang berubah.
return new class extends Migration
{
    private const JENJANG = [
        ['kode' => 'PAUD_A', 'label' => 'PAUD A (usia 3-4 tahun)', 'urutan' => 1, 'kategori_usia' => 'PAUD-A'],
        ['kode' => 'PAUD_B', 'label' => 'PAUD B (usia 4-5 tahun)', 'urutan' => 2, 'kategori_usia' => 'PAUD-B'],
        ['kode' => 'DASAR_1', 'label' => 'Dasar 1 (ACR)', 'urutan' => 3, 'kategori_usia' => 'ACR'],
        ['kode' => 'DASAR_2', 'label' => 'Dasar 2 (ACR)', 'urutan' => 4, 'kategori_usia' => 'ACR'],
        ['kode' => 'DASAR_3', 'label' => 'Dasar 3 (ACR)', 'urutan' => 5, 'kategori_usia' => 'ACR'],
        ['kode' => 'DASAR_4', 'label' => 'Dasar 4 (ACR)', 'urutan' => 6, 'kategori_usia' => 'ACR'],
        ['kode' => 'DASAR_5', 'label' => 'Dasar 5 (ACR)', 'urutan' => 7, 'kategori_usia' => 'ACR'],
        ['kode' => 'DASAR_6', 'label' => 'Dasar 6 (ACR)', 'urutan' => 8, 'kategori_usia' => 'ACR'],
        ['kode' => 'MENENGAH_7', 'label' => 'Menengah 7 (APR)', 'urutan' => 9, 'kategori_usia' => 'APR'],
        ['kode' => 'MENENGAH_8', 'label' => 'Menengah 8 (APR)', 'urutan' => 10, 'kategori_usia' => 'APR'],
        ['kode' => 'MENENGAH_9', 'label' => 'Menengah 9 (APR)', 'urutan' => 11, 'kategori_usia' => 'APR'],
        ['kode' => 'LANJUTAN_10', 'label' => 'Lanjutan 10 (AR)', 'urutan' => 12, 'kategori_usia' => 'AR'],
        ['kode' => 'LANJUTAN_11', 'label' => 'Lanjutan 11 (AR)', 'urutan' => 13, 'kategori_usia' => 'AR'],
        ['kode' => 'LANJUTAN_12', 'label' => 'Lanjutan 12 (AR)', 'urutan' => 14, 'kategori_usia' => 'AR'],
        ['kode' => 'GPN_A', 'label' => 'GPN-A', 'urutan' => 15, 'kategori_usia' => 'GPN-A'],
        ['kode' => 'GPN_B', 'label' => 'GPN-B', 'urutan' => 16, 'kategori_usia' => 'GPN-B'],
    ];

    public function up(): void
    {
        Schema::create('jenjang', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('label', 50);
            $table->unsignedSmallInteger('urutan');
            $table->string('kategori_usia', 10);
            $table->timestamps();
        });

        $now = now();
        DB::table('jenjang')->insert(array_map(
            fn ($row) => $row + ['created_at' => $now, 'updated_at' => $now],
            self::JENJANG
        ));

        Schema::table('kelas', function (Blueprint $table) {
            $table->foreignId('jenjang_id')->nullable()->after('jenjang')->constrained('jenjang');
        });

        foreach (DB::table('kelas')->get(['id', 'jenjang']) as $row) {
            DB::table('kelas')->where('id', $row->id)->update([
                'jenjang_id' => DB::table('jenjang')->where('kode', $row->jenjang)->value('id'),
            ]);
        }

        Schema::table('kelas', function (Blueprint $table) {
            $table->dropColumn('jenjang');
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->foreignId('jenjang_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->enum('jenjang', array_column(self::JENJANG, 'kode'))->nullable()->after('jenjang_id');
        });

        foreach (DB::table('kelas')->get(['id', 'jenjang_id']) as $row) {
            DB::table('kelas')->where('id', $row->id)->update([
                'jenjang' => DB::table('jenjang')->where('id', $row->jenjang_id)->value('kode'),
            ]);
        }

        Schema::table('kelas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jenjang_id');
            $table->enum('jenjang', array_column(self::JENJANG, 'kode'))->nullable(false)->change();
        });

        Schema::dropIfExists('jenjang');
    }
};
