<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Enkripsi PII nomor HP at-rest (SRS §17.2, §17.6). `akun_orang_tua.nomor_hp`
// butuh blind index (HMAC) karena dipakai untuk login & dedup exact-match,
// sesuatu yang tidak mungkin lagi langsung di kolom terenkripsi (IV acak).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akun_orang_tua', function (Blueprint $table) {
            $table->string('nomor_hp_hash', 64)->nullable()->after('nomor_hp');
        });

        DB::table('akun_orang_tua')->orderBy('id')->each(function ($row) {
            DB::table('akun_orang_tua')->where('id', $row->id)->update([
                'nomor_hp' => Crypt::encryptString($row->nomor_hp),
                'nomor_hp_hash' => hash_hmac('sha256', $row->nomor_hp, config('app.key')),
            ]);
        });

        Schema::table('akun_orang_tua', function (Blueprint $table) {
            $table->dropUnique(['nomor_hp']);
            $table->text('nomor_hp')->change();
            $table->string('nomor_hp_hash', 64)->nullable(false)->change();
        });

        Schema::table('akun_orang_tua', function (Blueprint $table) {
            $table->unique('nomor_hp_hash');
        });

        Schema::table('generus', function (Blueprint $table) {
            $table->dropIndex('idx_generus_nomor_hp');
        });

        DB::table('generus')->orderBy('id')->each(function ($row) {
            DB::table('generus')->where('id', $row->id)->update([
                'nomor_hp_orang_tua' => Crypt::encryptString($row->nomor_hp_orang_tua),
            ]);
        });

        Schema::table('generus', function (Blueprint $table) {
            $table->text('nomor_hp_orang_tua')->change();
        });

        DB::table('users')->whereNotNull('nomor_hp')->orderBy('id')->each(function ($row) {
            DB::table('users')->where('id', $row->id)->update([
                'nomor_hp' => Crypt::encryptString($row->nomor_hp),
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('nomor_hp')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('akun_orang_tua')->orderBy('id')->each(function ($row) {
            DB::table('akun_orang_tua')->where('id', $row->id)->update([
                'nomor_hp' => Crypt::decryptString($row->nomor_hp),
            ]);
        });

        DB::table('generus')->orderBy('id')->each(function ($row) {
            DB::table('generus')->where('id', $row->id)->update([
                'nomor_hp_orang_tua' => Crypt::decryptString($row->nomor_hp_orang_tua),
            ]);
        });

        DB::table('users')->whereNotNull('nomor_hp')->orderBy('id')->each(function ($row) {
            DB::table('users')->where('id', $row->id)->update([
                'nomor_hp' => Crypt::decryptString($row->nomor_hp),
            ]);
        });

        Schema::table('akun_orang_tua', function (Blueprint $table) {
            $table->dropUnique(['nomor_hp_hash']);
            $table->string('nomor_hp', 20)->change();
            $table->dropColumn('nomor_hp_hash');
        });

        Schema::table('akun_orang_tua', function (Blueprint $table) {
            $table->unique('nomor_hp');
        });

        Schema::table('generus', function (Blueprint $table) {
            $table->string('nomor_hp_orang_tua', 20)->change();
            $table->index('nomor_hp_orang_tua', 'idx_generus_nomor_hp');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('nomor_hp', 20)->nullable()->change();
        });
    }
};
