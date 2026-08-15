<?php

namespace App\Models\Concerns;

use App\Models\Kelompok;

/**
 * Cakupan Kelompok yang valid mengikuti `tingkat`/`penyelenggara_id` (SRS-Fase-1 §18.1)
 * — dipakai `Kegiatan` maupun `KegiatanJadwal` (SRS-Fase-2 §2.2), keduanya berbagi
 * bentuk polimorfik yang sama lewat HasPolymorphicPenyelenggara.
 */
trait HasKelompokCakupan
{
    public function kelompokCakupan()
    {
        return match ($this->tingkat) {
            'KELOMPOK' => Kelompok::where('id', $this->penyelenggara_id),
            'DESA' => Kelompok::where('desa_id', $this->penyelenggara_id),
            'DAERAH' => Kelompok::query(),
        };
    }
}
