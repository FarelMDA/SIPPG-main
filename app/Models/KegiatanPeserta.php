<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KegiatanPeserta extends Model
{
    protected $table = 'kegiatan_peserta';

    protected $fillable = [
        'kegiatan_id', 'generus_id', 'kelompok_id', 'kelas_id', 'status_presensi',
        'dicatat_oleh', 'client_uuid', 'field_updated_at',
    ];

    protected function casts(): array
    {
        return ['field_updated_at' => 'array'];
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    /** Didenormalisasi dari generus.kelas_id saat presensi dicatat — dasar breakdown laporan per-kelas. */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
