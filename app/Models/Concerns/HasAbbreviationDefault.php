<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Keputusan Teknis (di luar baseline SRS): Daerah/Desa/Kelompok punya kolom
 * `abbreviation` tambahan. Defaultnya selalu sama dengan `nama` kecuali diisi
 * eksplisit — trait ini menjamin aturan tsb berlaku di semua jalur simpan
 * (seeder, impor, maupun form Livewire), bukan cuma saat seeding awal.
 */
trait HasAbbreviationDefault
{
    protected static function bootHasAbbreviationDefault(): void
    {
        static::saving(function ($model) {
            if (Str::of((string) $model->abbreviation)->trim()->isEmpty()) {
                $model->abbreviation = $model->nama;
            }
        });
    }
}
