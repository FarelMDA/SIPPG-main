<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Akun Portal Orang Tua (guard `orangtua`), model auth terpisah dari `User`
 * (SRS §17.6, PRD §8). Login memakai nomor_hp sebagai username.
 */
class AkunOrangTua extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'akun_orang_tua';

    protected $guard_name = 'orangtua';

    protected $fillable = [
        'nomor_hp',
        'password',
        'must_change_password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'nomor_hp' => 'encrypted',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $akun) {
            if ($akun->isDirty('nomor_hp')) {
                $akun->nomor_hp_hash = static::hashNomorHp($akun->nomor_hp);
            }
        });
    }

    /** Blind index (HMAC-SHA256) untuk lookup exact-match nomor HP tanpa dekripsi (login & dedup). */
    public static function hashNomorHp(string $nomorHp): string
    {
        return hash_hmac('sha256', $nomorHp, config('app.key'));
    }

    public function generus(): BelongsToMany
    {
        return $this->belongsToMany(Generus::class, 'akun_orang_tua_generus');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(NotifikasiOrangTua::class);
    }
}
