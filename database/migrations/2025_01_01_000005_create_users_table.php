<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SRS §17.6 — akun internal (guard 'web'). Role & permission via spatie/laravel-permission.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('username', 100)->unique();
            $table->string('email')->nullable(); // opsional, tidak dipakai untuk auth (SRS §1.1)
            $table->string('password');
            $table->string('nomor_hp', 20)->nullable();
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompok'); // scope (§4.2)
            $table->foreignId('desa_id')->nullable()->constrained('desa'); // scope PJP Desa
            $table->boolean('must_change_password')->default(true);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
