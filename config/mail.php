<?php

return [

    // Email opsional di profil user, tidak dipakai untuk alur keamanan apa pun
    // di Fase 1 (SRS §1.1) — konfigurasi ini disediakan untuk kebutuhan umum saja.
    'default' => env('MAIL_MAILER', 'log'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@si-ppg.test'),
        'name' => env('MAIL_FROM_NAME', 'SI-PPG'),
    ],

];
