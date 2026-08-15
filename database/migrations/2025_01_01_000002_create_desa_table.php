<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.1
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daerah_id')->constrained('daerah');
            $table->string('nama');
            $table->string('abbreviation'); // singkatan nama — default sama dengan `nama` (lihat Concerns\HasAbbreviationDefault)
            $table->timestamps();

            $table->index('daerah_id', 'idx_desa_daerah');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desa');
    }
};
