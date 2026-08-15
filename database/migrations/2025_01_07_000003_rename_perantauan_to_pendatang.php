<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Ganti istilah 'PERANTAUAN' -> 'PENDATANG' di enum status_domisili (§4.1, §8),
// konsisten di 3 tabel: generus, generus_status_histories, sensus_snapshots.
return new class extends Migration
{
    // 'generus' punya default('SETEMPAT'), 2 tabel lain tidak — dipertahankan per tabel asalnya.
    private const TABEL_DEFAULT = ['generus' => 'SETEMPAT'];

    private const TABEL_TANPA_DEFAULT = ['generus_status_histories', 'sensus_snapshots'];

    public function up(): void
    {
        foreach (self::TABEL_DEFAULT as $tabel => $default) {
            Schema::table($tabel, function (Blueprint $table) use ($default) {
                $table->enum('status_domisili', ['SETEMPAT', 'PERANTAUAN', 'PENDATANG'])->default($default)->change();
            });
        }

        foreach (self::TABEL_TANPA_DEFAULT as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->enum('status_domisili', ['SETEMPAT', 'PERANTAUAN', 'PENDATANG'])->change();
            });
        }

        foreach ([...array_keys(self::TABEL_DEFAULT), ...self::TABEL_TANPA_DEFAULT] as $tabel) {
            DB::table($tabel)->where('status_domisili', 'PERANTAUAN')->update(['status_domisili' => 'PENDATANG']);
        }

        foreach (self::TABEL_DEFAULT as $tabel => $default) {
            Schema::table($tabel, function (Blueprint $table) use ($default) {
                $table->enum('status_domisili', ['SETEMPAT', 'PENDATANG'])->default($default)->change();
            });
        }

        foreach (self::TABEL_TANPA_DEFAULT as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->enum('status_domisili', ['SETEMPAT', 'PENDATANG'])->change();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABEL_DEFAULT as $tabel => $default) {
            Schema::table($tabel, function (Blueprint $table) use ($default) {
                $table->enum('status_domisili', ['SETEMPAT', 'PENDATANG', 'PERANTAUAN'])->default($default)->change();
            });
        }

        foreach (self::TABEL_TANPA_DEFAULT as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->enum('status_domisili', ['SETEMPAT', 'PENDATANG', 'PERANTAUAN'])->change();
            });
        }

        foreach ([...array_keys(self::TABEL_DEFAULT), ...self::TABEL_TANPA_DEFAULT] as $tabel) {
            DB::table($tabel)->where('status_domisili', 'PENDATANG')->update(['status_domisili' => 'PERANTAUAN']);
        }

        foreach (self::TABEL_DEFAULT as $tabel => $default) {
            Schema::table($tabel, function (Blueprint $table) use ($default) {
                $table->enum('status_domisili', ['SETEMPAT', 'PERANTAUAN'])->default($default)->change();
            });
        }

        foreach (self::TABEL_TANPA_DEFAULT as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->enum('status_domisili', ['SETEMPAT', 'PERANTAUAN'])->change();
            });
        }
    }
};
