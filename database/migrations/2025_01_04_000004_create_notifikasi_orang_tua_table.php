<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.6, §12.3 — notifikasi in-app (jalur utama, WhatsApp opsional Fase 2+).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi_orang_tua', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akun_orang_tua_id')->constrained('akun_orang_tua');
            $table->foreignId('generus_id')->constrained('generus');
            $table->enum('tipe', ['ALPHA'])->default('ALPHA');
            $table->string('pesan', 500);
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_orang_tua');
    }
};
