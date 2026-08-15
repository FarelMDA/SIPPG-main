<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.5
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('musyawaroh_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('musyawaroh_id')->constrained('musyawaroh')->cascadeOnDelete();
            $table->text('pokok_masalah');
            $table->text('keputusan')->nullable();
            $table->string('pic')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('musyawaroh_item');
    }
};
