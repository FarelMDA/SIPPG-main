<?php

namespace App\Models;

use App\Models\Concerns\BelongsToKelompok;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UC-27 — Kelola Catatan Konseling (Rekam Kasus). PRD §11.1: hanya `bk-kbm` (tulis),
 * `pjp-kelompok`/`admin-daerah` (baca) yang boleh mengakses data ini.
 */
class KonselingCatatan extends Model
{
    use BelongsToKelompok;

    protected $table = 'konseling_catatan';

    protected $fillable = ['kelompok_id', 'generus_id', 'tanggal', 'catatan', 'status', 'dicatat_oleh'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
