<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Ekspansi model-role (Struktur-Organisasi-dan-Role.md §Matriks, PRD v1.9 §6) — UC-15
// diperluas jadi multi-tingkat (Kelompok/Desa/Daerah, pola sama seperti `kegiatan`,
// SRS-Fase-1 §17.8) + pengesahan oleh Wanbin Daerah. Baris lama tetap KELOMPOK.
return new class extends Migration
{
    private const JENIS_LAMA = ['PENGURUS_KBM', 'LIMA_UNSUR', 'PERTEMUAN_LIMA_UNSUR', 'MUSTIN_LUPG'];

    private const JENIS_BARU = [...self::JENIS_LAMA, 'MUSYAWARAH_PPD_PPK', 'MUSYAWARAH_PPG_PPD'];

    public function up(): void
    {
        Schema::table('musyawaroh', function (Blueprint $table) {
            $table->foreignId('kelompok_id')->nullable()->change();
            $table->enum('tingkat', ['KELOMPOK', 'DESA', 'DAERAH'])->default('KELOMPOK')->after('kelompok_id');
            $table->enum('penyelenggara_type', ['kelompok', 'desa', 'daerah'])->nullable()->after('tingkat');
            $table->unsignedBigInteger('penyelenggara_id')->nullable()->after('penyelenggara_type');
            $table->enum('jenis', self::JENIS_BARU)->change();
            $table->foreignId('disahkan_oleh')->nullable()->after('jumlah_hadir')->constrained('users');
            $table->timestamp('disahkan_pada')->nullable()->after('disahkan_oleh');

            $table->index(['penyelenggara_type', 'penyelenggara_id'], 'idx_musyawaroh_penyelenggara');
        });

        DB::table('musyawaroh')->update([
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => DB::raw('kelompok_id'),
        ]);
    }

    public function down(): void
    {
        Schema::table('musyawaroh', function (Blueprint $table) {
            $table->dropIndex('idx_musyawaroh_penyelenggara');
            $table->dropConstrainedForeignId('disahkan_oleh');
            $table->dropColumn(['tingkat', 'penyelenggara_type', 'penyelenggara_id', 'disahkan_pada']);
            $table->enum('jenis', self::JENIS_LAMA)->change();
            $table->foreignId('kelompok_id')->nullable(false)->change();
        });
    }
};
