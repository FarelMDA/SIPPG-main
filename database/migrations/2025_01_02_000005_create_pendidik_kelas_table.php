<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.2 — many-to-many, satu Pendidik bisa mengampu >1 kelas.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendidik_kelas', function (Blueprint $table) {
            $table->foreignId('pendidik_id')->constrained('pendidik')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->primary(['pendidik_id', 'kelas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendidik_kelas');
    }
};
