<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.3, §8/§9.3 PRD — master kalender materi baku per jenjang.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurikulum_kalender', function (Blueprint $table) {
            $table->id();
            $table->string('jenjang', 20);
            $table->smallInteger('hari_ke');
            $table->json('item_materi');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['jenjang', 'hari_ke'], 'uq_kalender');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurikulum_kalender');
    }
};
