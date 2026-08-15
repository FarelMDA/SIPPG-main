<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MusyawarohItem extends Model
{
    protected $table = 'musyawaroh_item';

    protected $fillable = ['musyawaroh_id', 'pokok_masalah', 'keputusan', 'pic', 'keterangan'];

    public function musyawaroh(): BelongsTo
    {
        return $this->belongsTo(Musyawaroh::class);
    }
}
