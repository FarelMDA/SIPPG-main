<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** Master data pengganti ENUM `pendidik.jenis`. */
class JenisPendidik extends Model
{
    protected $table = 'jenis_pendidik';

    protected $fillable = ['kode', 'label', 'urutan'];

    /** Opsi <select>/radio yang submit kode (dipakai form Pendidik). */
    public static function options(): Collection
    {
        return static::orderBy('urutan')->pluck('label', 'kode');
    }
}
