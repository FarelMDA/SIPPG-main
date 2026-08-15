<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Master data jenjang usia (PAUD-A s.d. GPN-B) — gantikan ENUM `kelas.jenjang`
 * agar penambahan/perubahan jenjang cukup ubah baris data, bukan migration.
 */
class Jenjang extends Model
{
    protected $table = 'jenjang';

    protected $fillable = ['kode', 'label', 'urutan', 'kategori_usia'];

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    /** Opsi <select> yang submit id (dipakai Kelola Kelas — kelas.jenjang_id). */
    public static function options(): Collection
    {
        return static::orderBy('urutan')->pluck('label', 'id');
    }

    /** Opsi <select> yang submit kode string (dipakai Impor Kalender — kurikulum_kalender.jenjang tetap string). */
    public static function kodeOptions(): Collection
    {
        return static::orderBy('urutan')->pluck('label', 'kode');
    }

    /** Peta kode => kategori_usia, dipakai Sensus/Dashboard untuk mengelompokkan snapshot. */
    public static function kategoriUsiaMap(): array
    {
        return static::pluck('kategori_usia', 'kode')->all();
    }

    /** Urutan kategori usia unik (PAUD-A, PAUD-B, ACR, APR, AR, GPN-A, GPN-B) untuk tampilan Sensus. */
    public static function kategoriUrutan(): array
    {
        return static::orderBy('urutan')->pluck('kategori_usia')->unique()->values()->all();
    }
}
