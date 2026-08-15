<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SRS-Fase-2 §2.2, §10.1 — pola Jadwal Kegiatan Berulang (UC-28), menghasilkan banyak
 * baris `kegiatan` sekaligus lewat App\Services\Kegiatan\GeneratorKegiatanDariJadwal.
 * `penyelenggara_id` polimorfik tanpa FK constraint DB — pola sama `kegiatan.penyelenggara_id`
 * (SRS-Fase-1 §17.8), tabel targetnya berbeda tergantung `tingkat`.
 * Validasi "wajib jika X" (mis. minggu_ke_dalam_bulan wajib jika BULANAN) murni di
 * level aplikasi (Livewire), tidak ada preseden CHECK constraint di migrasi lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_jadwal', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->enum('tingkat', ['KELOMPOK', 'DESA', 'DAERAH']);
            $table->enum('penyelenggara_type', ['kelompok', 'desa', 'daerah']);
            $table->unsignedBigInteger('penyelenggara_id');
            $table->foreignId('jenis_kegiatan_id')->constrained('jenis_kegiatan');
            $table->enum('frekuensi_tipe', ['HARIAN', 'BULANAN', 'MINGGUAN_INTERVAL', 'KURIKULUM']);
            $table->json('hari_dalam_minggu');
            $table->json('minggu_ke_dalam_bulan')->nullable();
            $table->smallInteger('interval_minggu')->unsigned()->nullable();
            $table->smallInteger('jumlah_sesi_per_kemunculan')->unsigned()->default(1);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('tempat')->nullable();
            $table->json('rotasi_tempat')->nullable();
            $table->enum('target_tipe', ['SEMUA', 'JENJANG_KELAS', 'INDIVIDU'])->default('SEMUA');
            $table->foreignId('kegiatan_program_id')->nullable()->constrained('kegiatan_program');
            $table->enum('status', ['AKTIF', 'NONAKTIF'])->default('AKTIF');
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();

            $table->index(['penyelenggara_type', 'penyelenggara_id'], 'idx_kegiatan_jadwal_penyelenggara');
            $table->index('status', 'idx_kegiatan_jadwal_status');
            $table->index('kegiatan_program_id', 'idx_kegiatan_jadwal_program');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_jadwal');
    }
};
