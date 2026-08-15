<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kategori bawaan untuk KBM reguler hasil generate dari Kurikulum — supaya jalur
 * konvergensi langsung bisa dipakai tanpa Daerah harus membuat entri manual dulu
 * lewat Master Data Jenis Kegiatan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('jenis_kegiatan')->insertOrIgnore([
            'nama' => 'KBM Reguler',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('jenis_kegiatan')->where('nama', 'KBM Reguler')->delete();
    }
};
