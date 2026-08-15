<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.1 — Kelompok = KBM
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelompok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained('desa');
            $table->string('nama');
            $table->string('abbreviation'); // singkatan nama — default sama dengan `nama` (lihat Concerns\HasAbbreviationDefault)
            $table->text('alamat')->nullable();
            $table->timestamps();

            $table->index('desa_id', 'idx_kelompok_desa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelompok');
    }
};
