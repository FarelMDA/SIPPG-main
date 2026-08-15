<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Pecah jenjang 'PAUD' (usia 4-5 th) menjadi 'PAUD_A' (usia 3-4 th, baru)
// dan 'PAUD_B' (usia 4-5 th, existing) — pola sama seperti GPN_A/GPN_B (§4.1).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->enum('jenjang', [
                'PAUD', 'PAUD_A', 'PAUD_B',
                'DASAR_1', 'DASAR_2', 'DASAR_3', 'DASAR_4', 'DASAR_5', 'DASAR_6',
                'MENENGAH_7', 'MENENGAH_8', 'MENENGAH_9',
                'LANJUTAN_10', 'LANJUTAN_11', 'LANJUTAN_12',
                'GPN_A', 'GPN_B',
            ])->change();
        });

        DB::table('kelas')->where('jenjang', 'PAUD')->update(['jenjang' => 'PAUD_B']);

        Schema::table('kelas', function (Blueprint $table) {
            $table->enum('jenjang', [
                'PAUD_A', 'PAUD_B',
                'DASAR_1', 'DASAR_2', 'DASAR_3', 'DASAR_4', 'DASAR_5', 'DASAR_6',
                'MENENGAH_7', 'MENENGAH_8', 'MENENGAH_9',
                'LANJUTAN_10', 'LANJUTAN_11', 'LANJUTAN_12',
                'GPN_A', 'GPN_B',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->enum('jenjang', [
                'PAUD', 'PAUD_A', 'PAUD_B',
                'DASAR_1', 'DASAR_2', 'DASAR_3', 'DASAR_4', 'DASAR_5', 'DASAR_6',
                'MENENGAH_7', 'MENENGAH_8', 'MENENGAH_9',
                'LANJUTAN_10', 'LANJUTAN_11', 'LANJUTAN_12',
                'GPN_A', 'GPN_B',
            ])->change();
        });

        DB::table('kelas')->whereIn('jenjang', ['PAUD_A', 'PAUD_B'])->update(['jenjang' => 'PAUD']);

        Schema::table('kelas', function (Blueprint $table) {
            $table->enum('jenjang', [
                'PAUD',
                'DASAR_1', 'DASAR_2', 'DASAR_3', 'DASAR_4', 'DASAR_5', 'DASAR_6',
                'MENENGAH_7', 'MENENGAH_8', 'MENENGAH_9',
                'LANJUTAN_10', 'LANJUTAN_11', 'LANJUTAN_12',
                'GPN_A', 'GPN_B',
            ])->change();
        });
    }
};
