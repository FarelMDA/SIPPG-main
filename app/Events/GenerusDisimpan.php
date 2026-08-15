<?php

namespace App\Events;

use App\Models\Generus;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dipicu UC-05 (Kelola Generus)/UC-08 (Impor Massal), ditangkap UC-16
 * (Provisioning Akun Portal Orang Tua) — Struktur-Proyek §3.3.
 */
class GenerusDisimpan
{
    use Dispatchable;

    public function __construct(public Generus $generus) {}
}
