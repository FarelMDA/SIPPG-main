<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Kelas sungguhan di lapangan (mis. "Kelas ACR A") — beda dari tabel `kelas` yang
// murni junction Kelompok×Jenjang untuk pipeline KBM/presensi (tidak diubah). Satu
// RombonganBelajar bisa menggabungkan >1 Jenjang sekaligus (keterbatasan Guru/MT/MS
// di lapangan) — dipakai untuk pelaporan, bukan generate Kegiatan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rombongan_belajar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_id')->constrained('kelompok');
            $table->string('nama', 100);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('kelompok_id', 'idx_rombongan_belajar_kelompok');
        });

        Schema::create('rombongan_belajar_jenjang', function (Blueprint $table) {
            $table->foreignId('rombongan_belajar_id')->constrained('rombongan_belajar')->cascadeOnDelete();
            $table->foreignId('jenjang_id')->constrained('jenjang')->cascadeOnDelete();
            $table->primary(['rombongan_belajar_id', 'jenjang_id']);
        });

        // Backfill: Kelompok yang sudah ada sebelum RombonganBelajar dibuat (auto-provisioning
        // di KelolaKelompok::simpan() cuma jalan saat Kelompok BARU dibuat) tetap perlu 1
        // RombonganBelajar per Jenjang sebagai starting point — sama seperti backfill Kelas
        // di migration 2025_02_03_000000_simplify_kelas_table.
        $jenjangs = DB::table('jenjang')->orderBy('urutan')->get(['id', 'label']);
        $kelompokIds = DB::table('kelompok')->whereNull('deleted_at')->pluck('id');
        $now = now();

        foreach ($kelompokIds as $kelompokId) {
            foreach ($jenjangs as $jenjang) {
                $rombelId = DB::table('rombongan_belajar')->insertGetId([
                    'kelompok_id' => $kelompokId,
                    'nama' => $jenjang->label,
                    'status_aktif' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('rombongan_belajar_jenjang')->insert([
                    'rombongan_belajar_id' => $rombelId,
                    'jenjang_id' => $jenjang->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rombongan_belajar_jenjang');
        Schema::dropIfExists('rombongan_belajar');
    }
};
