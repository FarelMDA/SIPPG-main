<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * Akun internal (guard `web`): Admin Daerah, PJP Desa, PJP Kelompok,
 * Sekretaris KBM, Guru (SRS §5.1). HasApiTokens dipakai KHUSUS untuk token
 * sinkronisasi offline (SRS §2.1, §13) — bukan jalur login utama.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    protected $guard_name = 'web';

    protected $fillable = [
        'nama',
        'username',
        'email',
        'password',
        'nomor_hp',
        'kelompok_id',
        'desa_id',
        'pendidik_id',
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
            'password' => 'hashed',
            'nomor_hp' => 'encrypted',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    public function pendidik(): BelongsTo
    {
        return $this->belongsTo(Pendidik::class);
    }

    /**
     * PJP Desa belum punya Dashboard di Fase 1 (SRS §1.1, dashboard/agregasi Desa
     * menyusul Fase 2 — UCIC-Fase-2 UC-36) — mendarat di Kelola Struktur Organisasi
     * (satu-satunya halaman read-only yang sudah mereka punya akses) sebagai gantinya.
     *
     * Role hasil ekspansi model-role (UIUX-Reference-Fase-1 §2.1) mendarat di halaman
     * yang sesuai dengan permission sempit masing-masing, bukan Dashboard generik —
     * kebanyakan cuma punya 1 modul yang benar-benar bisa diakses.
     */
    public function landingRouteName(): string
    {
        return match (true) {
            $this->hasRole('pjp-desa') => 'master-data.struktur-organisasi',
            $this->hasRole('wanbin-desa'), $this->hasRole('sekretaris-ppg') => 'musyawaroh.index',
            $this->hasRole('bidang-kurikulum'), $this->hasRole('pakar-pendidik') => 'kurikulum',
            $this->hasRole('bidang-tendik') => 'sensus.pendidik',
            $this->hasRole('sekretaris-desa') => 'sensus.generus',
            $this->hasRole('bk-kbm') => 'konseling.index',
            default => 'dashboard',
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'username', 'kelompok_id', 'desa_id', 'is_active'])
            ->logOnlyDirty();
    }
}
