<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kelas.nama selalu duplikat Jenjang.label (tidak pernah dikustomisasi per-Kelompok) —
 * jadi dijadikan accessor turunan (App\Models\Kelas::nama), bukan kolom. Sebagai
 * gantinya tambah status_aktif supaya "Kelompok X tidak menyelenggarakan Kelas Y
 * tahun ini" bisa direpresentasikan tanpa hapus data (riwayat Generus/Kegiatan tetap
 * aman). Baris Kelas tetap 1 per Kelompok×Jenjang (kelompok_id TIDAK dipindah —
 * RBAC scoping & relasi kegiatan_peserta.kelas_id bergantung padanya), tapi sekarang
 * dibuat otomatis (lihat KelolaKelompok::simpan) alih-alih diketik manual berulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->boolean('status_aktif')->default(true)->after('jenjang_id');
            $table->string('nama', 100)->nullable()->change();
        });

        // Backfill: pastikan tiap Kelompok yang sudah ada punya 1 baris Kelas per Jenjang.
        $jenjangIds = DB::table('jenjang')->pluck('id');
        $kelompokIds = DB::table('kelompok')->whereNull('deleted_at')->pluck('id');
        $now = now();

        foreach ($kelompokIds as $kelompokId) {
            $existing = DB::table('kelas')
                ->where('kelompok_id', $kelompokId)
                ->pluck('jenjang_id')
                ->all();

            $missing = $jenjangIds->diff($existing);

            if ($missing->isEmpty()) {
                continue;
            }

            DB::table('kelas')->insert($missing->map(fn ($jenjangId) => [
                'kelompok_id' => $kelompokId,
                'jenjang_id' => $jenjangId,
                'status_aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }

        Schema::table('kelas', function (Blueprint $table) {
            $table->dropColumn('nama');
            $table->unique(['kelompok_id', 'jenjang_id'], 'uq_kelas_kelompok_jenjang');
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropUnique('uq_kelas_kelompok_jenjang');
            $table->string('nama', 100)->nullable()->after('kelompok_id');
        });

        DB::table('kelas')->update([
            'nama' => DB::raw('(select label from jenjang where jenjang.id = kelas.jenjang_id)'),
        ]);

        Schema::table('kelas', function (Blueprint $table) {
            $table->string('nama', 100)->nullable(false)->change();
            $table->dropColumn('status_aktif');
        });
    }
};
