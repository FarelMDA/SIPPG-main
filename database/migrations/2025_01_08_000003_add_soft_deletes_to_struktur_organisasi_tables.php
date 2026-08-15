<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// UCIC UC-04 — hapus() Desa/Kelompok/Kelas adalah soft-delete, bukan hard-delete.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('desa', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('kelompok', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('desa', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('kelompok', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
