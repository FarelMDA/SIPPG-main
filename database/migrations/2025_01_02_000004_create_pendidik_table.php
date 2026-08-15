<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.2
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendidik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_id')->constrained('kelompok');
            $table->string('nama');
            $table->enum('jenis', ['MT', 'MS']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendidik');
    }
};
