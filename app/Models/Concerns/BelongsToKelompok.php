<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Eloquent Global Scope yang membatasi query otomatis ke kelompok_id milik user
 * yang login (SRS §4.2) — dipakai Pendidik, Musyawaroh, ProgramMonitoring.
 * Admin Daerah di-bypass penuh (scope Daerah). PJP Desa dibatasi ke seluruh
 * Kelompok dalam Desa miliknya (multi-kelompok, tapi tetap satu Desa).
 *
 * Karena tidak ada multi-tenant (PRD §18.4), scoping ini murni foreign key,
 * bukan isolasi database/skema terpisah.
 */
trait BelongsToKelompok
{
    protected static function bootBelongsToKelompok(): void
    {
        static::addGlobalScope('kelompok', function (Builder $builder) {
            $user = Auth::guard('web')->user();

            if (! $user) {
                return;
            }

            if ($user->hasRole('admin-daerah')) {
                return;
            }

            $table = $builder->getModel()->getTable();

            if ($user->hasRole('pjp-desa')) {
                $builder->whereIn($table.'.kelompok_id', function ($query) use ($user) {
                    $query->select('id')->from('kelompok')->where('desa_id', $user->desa_id);
                });

                return;
            }

            $builder->where($table.'.kelompok_id', $user->kelompok_id);
        });
    }
}
