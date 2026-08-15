<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Generus extends Model
{
    use HasFactory;

    protected $table = 'generus';

    protected $fillable = [
        'nama',
        'tanggal_lahir',
        'jenis_kelamin',
        'kelas_id',
        'jenjang_id',
        'nama_orang_tua',
        'nomor_hp_orang_tua',
        'status_domisili',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'nomor_hp_orang_tua' => 'encrypted',
            'status_aktif' => 'boolean',
        ];
    }

    /**
     * Global Scope `kelompok_id` (§4.2) diterapkan lewat kolom `kelas.kelompok_id` — lihat
     * scopeKelompok(). `pakar-pendidik` di-bypass (akses `generus.view` lintas-Kelompok
     * se-Daerah, Struktur-Organisasi-dan-Role.md §D) dan `sekretaris-desa` mengikuti pola
     * `pjp-desa` (akses `generus.view` se-Desa, bukan se-Kelompok).
     */
    protected static function booted(): void
    {
        static::addGlobalScope('kelompokViaKelas', function ($builder) {
            $user = auth('web')->user();

            if (! $user || $user->hasRole('admin-daerah') || $user->hasRole('pakar-pendidik')) {
                return;
            }

            if ($user->hasRole('pjp-desa') || $user->hasRole('sekretaris-desa')) {
                $builder->whereHas('kelas.kelompok', function ($query) use ($user) {
                    $query->where('desa_id', $user->desa_id);
                });

                return;
            }

            $builder->whereHas('kelas', function ($query) use ($user) {
                $query->where('kelompok_id', $user->kelompok_id);
            });
        });
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(GenerusStatusHistory::class);
    }

    public function kelasHistories(): HasMany
    {
        return $this->hasMany(GenerusKelasHistory::class);
    }

    public function akunOrangTua(): BelongsToMany
    {
        return $this->belongsToMany(AkunOrangTua::class, 'akun_orang_tua_generus');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(NotifikasiOrangTua::class);
    }

    public function kegiatanPeserta(): HasMany
    {
        return $this->hasMany(KegiatanPeserta::class);
    }
}
