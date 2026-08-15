<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS-Fase-2 §2.6/§2.6.1, §10.1 — kalender hari libur Daerah-wide (UC-38, UC-39, UC-41).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hari_libur', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('sumber', ['MANUAL', 'OTOMATIS_GOOGLE'])->default('MANUAL');
            $table->string('google_event_id')->nullable()->unique();
            $table->boolean('disunting_manual')->default(false);
            $table->timestamp('dihapus_pada')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['tanggal_mulai', 'tanggal_selesai'], 'idx_hari_libur_rentang');
            $table->index(['sumber', 'dihapus_pada'], 'idx_hari_libur_sumber');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_libur');
    }
};
