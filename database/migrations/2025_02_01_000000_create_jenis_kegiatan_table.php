<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Jenis Kegiatan jadi tabel master data sendiri, gantikan ENUM di kegiatan.jenis/
// kegiatan_jadwal.jenis — dikelola Admin Daerah lewat Master Data (jenis-kegiatan.manage),
// role di bawahnya hanya memilih dari daftar yang sudah ada. Pola sama jenjang
// (2025_01_07_000004_create_jenjang_table.php), tapi tanpa kolom `kode` terpisah
// karena tidak ada tabel lain yang soft-reference nilai ini sebagai string.
return new class extends Migration
{
    private const JENIS = ['Tambahan', 'Penguatan', 'Program Khusus', 'Ekstrakurikuler'];

    public function up(): void
    {
        Schema::create('jenis_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->timestamps();
        });

        $now = now();
        DB::table('jenis_kegiatan')->insert(array_map(
            fn ($nama) => ['nama' => $nama, 'created_at' => $now, 'updated_at' => $now],
            self::JENIS
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_kegiatan');
    }
};
