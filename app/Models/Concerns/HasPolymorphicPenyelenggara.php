<?php

namespace App\Models\Concerns;

use App\Models\Daerah;
use App\Models\Desa;
use App\Models\Kelompok;

/**
 * Helper accessor Kegiatan->penyelenggara (Kelompok/Desa/Daerah) — SRS §18.1.
 * `penyelenggara_id` bersifat polimorfik mengikuti `tingkat`, tanpa FK constraint
 * DB (tabel target berbeda-beda), jadi diselesaikan manual di sini.
 */
trait HasPolymorphicPenyelenggara
{
    public function getPenyelenggaraAttribute(): Kelompok|Desa|Daerah|null
    {
        return match ($this->penyelenggara_type) {
            'kelompok' => Kelompok::find($this->penyelenggara_id),
            'desa' => Desa::find($this->penyelenggara_id),
            'daerah' => Daerah::find($this->penyelenggara_id),
            default => null,
        };
    }

    public function getPenyelenggaraNamaAttribute(): ?string
    {
        return $this->penyelenggara?->nama;
    }
}
