<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.2 — riwayat perubahan status domisili, bukan menimpa field di generus.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generus_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generus_id')->constrained('generus');
            $table->enum('status_domisili', ['SETEMPAT', 'PERANTAUAN']);
            $table->date('berlaku_sejak');
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generus_status_histories');
    }
};
