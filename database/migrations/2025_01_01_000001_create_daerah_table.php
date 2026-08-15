<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.1 — hanya satu baris (PRD §18.4), tidak multi-tenant.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daerah', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('abbreviation'); // singkatan nama — default sama dengan `nama` (lihat Concerns\HasAbbreviationDefault)
            $table->text('alamat_sekretariat')->nullable();
            $table->text('visi')->nullable();
            $table->text('misi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daerah');
    }
};
