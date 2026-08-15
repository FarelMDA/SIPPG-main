<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.6 — many-to-many akun_orang_tua <-> generus (PRD §8).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akun_orang_tua_generus', function (Blueprint $table) {
            $table->foreignId('akun_orang_tua_id')->constrained('akun_orang_tua')->cascadeOnDelete();
            $table->foreignId('generus_id')->constrained('generus')->cascadeOnDelete();
            $table->primary(['akun_orang_tua_id', 'generus_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akun_orang_tua_generus');
    }
};
