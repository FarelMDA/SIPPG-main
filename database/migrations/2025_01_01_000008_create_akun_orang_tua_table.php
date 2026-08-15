<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.6 — akun Portal Orang Tua (guard 'orangtua'), terpisah dari `users`.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akun_orang_tua', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_hp', 20)->unique(); // username login
            $table->string('password');
            $table->boolean('must_change_password')->default(true);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akun_orang_tua');
    }
};
