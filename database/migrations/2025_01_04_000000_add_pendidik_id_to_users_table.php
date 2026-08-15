<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Keputusan Teknis: SRS §17.6 tidak menautkan `users` (akun Guru) ke roster
 * `pendidik` (§17.2) — celah kecil karena scope akses Guru (SRS §4.2: "Kelas
 * yang diampu") butuh sumber tautan yang stabil, bukan pencocokan nama string.
 * Kolom nullable ini mengisi celah tsb tanpa mengubah skema yang sudah dikontrak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('pendidik_id')->nullable()->after('desa_id')->constrained('pendidik');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pendidik_id');
        });
    }
};
