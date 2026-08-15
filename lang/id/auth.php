<?php

// Tidak dipakai untuk pesan login (SI-PPG punya pesan error sendiri di
// Auth\LoginForm/LoginOrangTuaForm sesuai UCIC UC-01) — disediakan untuk
// jaga-jaga bila ada bagian framework yang merujuk ke sini.
return [
    'failed' => 'Kredensial ini tidak cocok dengan data kami.',
    'password' => 'Password yang diberikan salah.',
    'throttle' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam :seconds detik.',
];
