<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gender, Status Domisili, Jenis Pendidik, dan Status Presensi jadi tabel master data
 * sendiri — pola sama seperti `jenjang` (lihat 2025_01_07_000004_create_jenjang_table.php):
 * gantikan ENUM supaya penambahan/perubahan nilai cukup ubah baris data, bukan migration
 * schema. Kolom konsumen (generus.jenis_kelamin, dst.) tetap VARCHAR soft-reference ke
 * `kode` — bukan foreign key keras — karena dipakai lewat perbandingan string langsung
 * di banyak tempat (badge, business logic), sama seperti kurikulum_kalender.jenjang.
 */
return new class extends Migration
{
    private const JENIS_KELAMIN = [
        ['kode' => 'LAKI', 'label' => 'Laki-laki', 'urutan' => 1],
        ['kode' => 'PEREMPUAN', 'label' => 'Perempuan', 'urutan' => 2],
    ];

    private const STATUS_DOMISILI = [
        ['kode' => 'SETEMPAT', 'label' => 'Setempat', 'urutan' => 1],
        ['kode' => 'PENDATANG', 'label' => 'Pendatang', 'urutan' => 2],
    ];

    private const JENIS_PENDIDIK = [
        ['kode' => 'MT', 'label' => 'Muballigh Tugasan (MT)', 'urutan' => 1],
        ['kode' => 'MS', 'label' => 'Muballigh Setempat (MS)', 'urutan' => 2],
    ];

    private const STATUS_PRESENSI = [
        ['kode' => 'HADIR', 'label' => 'Hadir', 'urutan' => 1],
        ['kode' => 'IZIN', 'label' => 'Izin', 'urutan' => 2],
        ['kode' => 'SAKIT', 'label' => 'Sakit', 'urutan' => 3],
        ['kode' => 'ALPHA', 'label' => 'Alpha', 'urutan' => 4],
    ];

    public function up(): void
    {
        $now = now();

        foreach ([
            'jenis_kelamin' => self::JENIS_KELAMIN,
            'status_domisili' => self::STATUS_DOMISILI,
            'jenis_pendidik' => self::JENIS_PENDIDIK,
            'status_presensi' => self::STATUS_PRESENSI,
        ] as $table => $rows) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('kode', 20)->unique();
                $t->string('label', 50);
                $t->unsignedSmallInteger('urutan');
                $t->timestamps();
            });

            DB::table($table)->insert(array_map(
                fn ($row) => $row + ['created_at' => $now, 'updated_at' => $now],
                $rows
            ));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('status_presensi');
        Schema::dropIfExists('jenis_pendidik');
        Schema::dropIfExists('status_domisili');
        Schema::dropIfExists('jenis_kelamin');
    }
};
