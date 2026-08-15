<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.8
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_monitoring_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_monitoring_id')->constrained('program_monitoring')->cascadeOnDelete();
            $table->foreignId('generus_id')->nullable()->constrained('generus');
            $table->text('temuan')->nullable();
            $table->string('pic')->nullable();
            $table->enum('status_item', ['BELUM', 'PROSES', 'SELESAI'])->default('BELUM');
            $table->date('tenggat_item')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_monitoring_item');
    }
};
