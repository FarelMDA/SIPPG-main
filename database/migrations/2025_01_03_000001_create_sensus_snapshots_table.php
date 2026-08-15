<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.2, §7 — snapshot bulanan untuk tren perbandingan antar bulan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensus_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_id')->constrained('kelompok');
            $table->string('periode', 7); // 'YYYY-MM'
            $table->string('jenjang', 20);
            $table->enum('status_domisili', ['SETEMPAT', 'PERANTAUAN']);
            $table->enum('jenis_kelamin', ['LAKI', 'PEREMPUAN']);
            $table->integer('jumlah')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['kelompok_id', 'periode', 'jenjang', 'status_domisili', 'jenis_kelamin'],
                'uq_sensus'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensus_snapshots');
    }
};
