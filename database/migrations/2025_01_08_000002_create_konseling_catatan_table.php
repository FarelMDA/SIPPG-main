<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// UC-27 — Kelola Catatan Konseling (Rekam Kasus), Struktur-Organisasi-dan-Role.md §C.
// Fitur sederhana Fase 1-2: teks bebas, tanpa struktur/kategori kasus. Visibilitas
// dibatasi (PRD §11.1) — hanya bk-kbm (tulis), pjp-kelompok/admin-daerah (baca).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konseling_catatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_id')->constrained('kelompok');
            $table->foreignId('generus_id')->constrained('generus');
            $table->date('tanggal');
            $table->text('catatan');
            $table->enum('status', ['BERLANGSUNG', 'SELESAI'])->default('BERLANGSUNG');
            $table->foreignId('dicatat_oleh')->constrained('users');
            $table->timestamps();

            $table->index('kelompok_id', 'idx_konseling_kelompok');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konseling_catatan');
    }
};
