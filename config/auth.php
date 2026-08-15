<?php

return [

    // Fase 1 memakai dua guard terpisah — 'web' untuk akun internal (Admin Daerah,
    // PJP Desa/Kelompok, Sekretaris KBM, Guru) dan 'orangtua' untuk Portal Orang Tua.
    // Session/token tidak pernah dipertukarkan antar guard (SRS §3, §4.2).
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'orangtua' => [
            'driver' => 'session',
            'provider' => 'akun_orang_tua',
        ],

        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        'akun_orang_tua' => [
            'driver' => 'eloquent',
            'model' => App\Models\AkunOrangTua::class,
        ],
    ],

    // Tidak dipakai (Fase 1 tidak punya reset password self-service via email,
    // SRS §1.1/§3.4 — reset selalu manual oleh Admin/PJP). Dibiarkan default agar
    // paket bawaan Laravel yang membaca konfigurasi ini tidak error.
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
