<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiOrangTua extends Model
{
    public $timestamps = false;

    protected $table = 'notifikasi_orang_tua';

    protected $fillable = ['akun_orang_tua_id', 'generus_id', 'tipe', 'pesan', 'dibaca_pada'];

    protected function casts(): array
    {
        return [
            'dibaca_pada' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function akunOrangTua(): BelongsTo
    {
        return $this->belongsTo(AkunOrangTua::class, 'akun_orang_tua_id');
    }

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }
}
