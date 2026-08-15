<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Master data Jenis Musyawaroh — gantikan ENUM `musyawaroh.jenis` agar Admin Daerah
 * bisa menambah/mengubah jenis lewat Master Data, tanpa migration. Pilihan scoped per
 * `tingkat` (KELOMPOK/DESA/DAERAH — lihat KelolaMusyawaroh::tingkatSaya()).
 * `perlu_jumlah_hadir` gantikan pengecekan hardcoded `jenis === 'MUSTIN_LUPG'`.
 */
class JenisMusyawaroh extends Model
{
    use HasFactory;

    protected $table = 'jenis_musyawaroh';

    protected $fillable = ['nama', 'tingkat', 'urutan', 'perlu_jumlah_hadir'];

    protected function casts(): array
    {
        return [
            'perlu_jumlah_hadir' => 'boolean',
        ];
    }

    public function musyawaroh(): HasMany
    {
        return $this->hasMany(Musyawaroh::class);
    }

    /** Opsi <select> yang submit id, scoped per tingkat (dipakai KelolaMusyawaroh). */
    public static function options(string $tingkat): Collection
    {
        return static::where('tingkat', $tingkat)->orderBy('urutan')->pluck('nama', 'id');
    }
}
