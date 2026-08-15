<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.2 — riwayat kenaikan kelas per semester.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generus_kelas_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generus_id')->constrained('generus');
            $table->foreignId('kelas_id')->constrained('kelas');
            $table->string('semester', 20); // mis. '2026-Ganjil'
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generus_kelas_histories');
    }
};
