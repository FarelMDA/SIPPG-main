<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Master kalender materi per jenjang, berbasis rentang tanggal kalender literal
 * (bukan lagi urutan hari mengajar) — dipakai App\Services\Kegiatan\
 * GeneratorKegiatanDariJadwal untuk generate Kegiatan KBM Reguler per Kelas.
 * `jenis=MUNAQOSAH` murni penjadwalan kejadian, belum ada struktur penilaian
 * (menyusul Fase 3, PRD §9.9).
 */
class KurikulumKalender extends Model
{
    protected $table = 'kurikulum_kalender';

    protected $fillable = ['jenjang', 'tanggal_mulai', 'tanggal_selesai', 'jenis', 'item_materi', 'keterangan', 'dibuat_oleh'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'item_materi' => 'array',
        ];
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
