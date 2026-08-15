<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** Master data pengganti ENUM `generus.jenis_kelamin`/`sensus_snapshots.jenis_kelamin`. */
class JenisKelamin extends Model
{
    protected $table = 'jenis_kelamin';

    protected $fillable = ['kode', 'label', 'urutan'];

    /** Opsi <select>/radio yang submit kode (dipakai form Generus). */
    public static function options(): Collection
    {
        return static::orderBy('urutan')->pluck('label', 'kode');
    }
}
