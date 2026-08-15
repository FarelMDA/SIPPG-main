<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** Master data pengganti ENUM `kegiatan_peserta.status_presensi`. */
class StatusPresensi extends Model
{
    protected $table = 'status_presensi';

    protected $fillable = ['kode', 'label', 'urutan'];

    /** Opsi <select>/radio yang submit kode (dipakai form Input Presensi Kegiatan). */
    public static function options(): Collection
    {
        return static::orderBy('urutan')->pluck('label', 'kode');
    }
}
