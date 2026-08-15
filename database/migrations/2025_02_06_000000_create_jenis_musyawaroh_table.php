<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Jenis Musyawaroh jadi tabel master data sendiri, gantikan ENUM di musyawaroh.jenis —
// dikelola Admin Daerah lewat Master Data (jenis-musyawaroh.manage), pola sama
// jenis_kegiatan (2025_02_01_000000_create_jenis_kegiatan_table.php). Beda dengan Jenis
// Kegiatan: pilihan di sini scoped per `tingkat` (KELOMPOK/DESA/DAERAH — lihat
// KelolaMusyawaroh::JENIS_PER_TINGKAT lama), jadi kolom `tingkat` ikut disimpan di sini,
// bukan cuma difilter di kode. `perlu_jumlah_hadir` gantikan pengecekan hardcoded
// `jenis === 'MUSTIN_LUPG'` (satu-satunya jenis yang mewajibkan input Jumlah Hadir).
return new class extends Migration
{
    private const JENIS = [
        ['nama' => 'Musyawaroh Pengurus KBM', 'tingkat' => 'KELOMPOK', 'urutan' => 1, 'perlu_jumlah_hadir' => false],
        ['nama' => 'Musyawaroh 5 Unsur', 'tingkat' => 'KELOMPOK', 'urutan' => 2, 'perlu_jumlah_hadir' => false],
        ['nama' => 'Pertemuan 5 Unsur', 'tingkat' => 'KELOMPOK', 'urutan' => 3, 'perlu_jumlah_hadir' => false],
        ['nama' => 'Mustin LUPG', 'tingkat' => 'KELOMPOK', 'urutan' => 4, 'perlu_jumlah_hadir' => true],
        ['nama' => 'Musyawarah PPD-PPK', 'tingkat' => 'DESA', 'urutan' => 1, 'perlu_jumlah_hadir' => false],
        ['nama' => 'Musyawarah PPG-PPD', 'tingkat' => 'DAERAH', 'urutan' => 1, 'perlu_jumlah_hadir' => false],
    ];

    public function up(): void
    {
        Schema::create('jenis_musyawaroh', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('tingkat', ['KELOMPOK', 'DESA', 'DAERAH']);
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->boolean('perlu_jumlah_hadir')->default(false);
            $table->timestamps();

            $table->unique(['tingkat', 'nama']);
        });

        $now = now();
        DB::table('jenis_musyawaroh')->insert(array_map(
            fn ($row) => $row + ['created_at' => $now, 'updated_at' => $now],
            self::JENIS
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_musyawaroh');
    }
};
