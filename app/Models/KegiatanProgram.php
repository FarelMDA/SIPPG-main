<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Label pengelompokan lintas-tingkat untuk rekap gabungan (SRS-Fase-2 §2.8, UC-40) —
 * murni metadata pencatatan, tidak mengubah validasi cakupan Jadwal/Kegiatan yang ditandainya.
 */
class KegiatanProgram extends Model
{
    protected $table = 'kegiatan_program';

    public $timestamps = false;

    protected $fillable = ['nama', 'tingkat_tertinggi', 'dibuat_oleh'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function kegiatanJadwal(): HasMany
    {
        return $this->hasMany(KegiatanJadwal::class, 'kegiatan_program_id');
    }

    public function kegiatan(): HasMany
    {
        return $this->hasMany(Kegiatan::class, 'kegiatan_program_id');
    }
}
