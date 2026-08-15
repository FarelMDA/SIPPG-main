<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Generus butuh Jenjang individual sendiri, terpisah dari kelas_id — karena satu
// RombonganBelajar (Kelas sungguhan) bisa menggabungkan >1 Jenjang, kelas_id saja
// tidak lagi cukup untuk tahu Jenjang sebenarnya seorang Generus (dipakai sensus/
// rollup per-Jenjang). kelas_id TETAP ada — itu yang dipakai pipeline KBM/presensi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generus', function (Blueprint $table) {
            $table->foreignId('jenjang_id')->nullable()->after('kelas_id')->constrained('jenjang');
        });

        DB::table('generus')->update([
            'jenjang_id' => DB::raw('(select jenjang_id from kelas where kelas.id = generus.kelas_id)'),
        ]);

        Schema::table('generus', function (Blueprint $table) {
            $table->foreignId('jenjang_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('generus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jenjang_id');
        });
    }
};
