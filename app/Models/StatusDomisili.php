<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** Master data pengganti ENUM `generus.status_domisili`/`sensus_snapshots.status_domisili`/`generus_status_histories.status_domisili`. */
class StatusDomisili extends Model
{
    protected $table = 'status_domisili';

    protected $fillable = ['kode', 'label', 'urutan'];

    /** Opsi <select>/radio yang submit kode (dipakai form & filter Generus). */
    public static function options(): Collection
    {
        return static::orderBy('urutan')->pluck('label', 'kode');
    }
}
