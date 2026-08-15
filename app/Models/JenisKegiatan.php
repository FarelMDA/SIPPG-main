<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Master data Jenis Kegiatan — gantikan ENUM `kegiatan.jenis`/`kegiatan_jadwal.jenis`
 * agar Admin Daerah bisa menambah/mengubah jenis lewat Master Data, tanpa migration.
 */
class JenisKegiatan extends Model
{
    use HasFactory;

    protected $table = 'jenis_kegiatan';

    protected $fillable = ['nama'];

    public function kegiatan(): HasMany
    {
        return $this->hasMany(Kegiatan::class);
    }

    public function kegiatanJadwal(): HasMany
    {
        return $this->hasMany(KegiatanJadwal::class);
    }

    /** Opsi <select> yang submit id (dipakai FormKegiatan/FormJadwalKegiatan). */
    public static function options(): Collection
    {
        return static::orderBy('nama')->pluck('nama', 'id');
    }
}
