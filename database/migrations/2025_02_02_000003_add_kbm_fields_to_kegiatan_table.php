<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Field KBM-sebagai-Kegiatan (docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5) —
 * `kurikulum_kalender_id` null untuk Kegiatan non-KBM. `materi` adalah SNAPSHOT
 * `kurikulum_kalender.item_materi` saat generate (bukan rujukan dinamis), pola sama
 * snapshot target_kelas/target_individu yang sudah ada. `realisasi_status`/`realisasi_catatan`
 * menggantikan konsep sama di Jurnal Harian lama. `field_updated_at` untuk konflik
 * offline per-field (pola sama Presensi/Jurnal Harian lama).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->foreignId('kurikulum_kalender_id')->nullable()->after('kegiatan_jadwal_id')->constrained('kurikulum_kalender')->nullOnDelete();
            $table->json('materi')->nullable()->after('kurikulum_kalender_id');
            $table->enum('realisasi_status', ['SESUAI_JADWAL', 'TIDAK_TERLAKSANA', 'PENGGANTI'])->nullable()->after('materi');
            $table->text('realisasi_catatan')->nullable()->after('realisasi_status');
            $table->json('field_updated_at')->nullable()->after('realisasi_catatan');
        });
    }

    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kurikulum_kalender_id');
            $table->dropColumn(['materi', 'realisasi_status', 'realisasi_catatan', 'field_updated_at']);
        });
    }
};
