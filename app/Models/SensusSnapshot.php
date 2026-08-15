<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensusSnapshot extends Model
{
    public $timestamps = false;

    protected $table = 'sensus_snapshots';

    protected $fillable = ['kelompok_id', 'periode', 'jenjang', 'status_domisili', 'jenis_kelamin', 'jumlah'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }
}
