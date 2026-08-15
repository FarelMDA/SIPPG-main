<?php

namespace App\Events;

use App\Models\HariLibur;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dipicu UC-38 (Kelola Kalender Hari Libur, manual)/UC-41 (Sinkronisasi Google Calendar),
 * ditangkap UC-39 (Batalkan Kegiatan Karena Libur — SRS-Fase-2 §2.6).
 */
class HariLiburDisimpan
{
    use Dispatchable;

    public function __construct(public HariLibur $hariLibur) {}
}
