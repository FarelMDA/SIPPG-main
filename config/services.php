<?php

return [

    // Notifikasi WhatsApp bersifat tambahan opsional, bukan jalur utama di Fase 1
    // (PRD §16, §17.3; SRS §1.1) — konfigurasi gateway disiapkan untuk Fase 2+.
    'whatsapp' => [
        'gateway_url' => env('WHATSAPP_GATEWAY_URL'),
        'token' => env('WHATSAPP_GATEWAY_TOKEN'),
    ],

];
