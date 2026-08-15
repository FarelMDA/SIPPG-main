<?php

namespace App\Listeners;

use App\Events\GenerusDisimpan;
use App\Models\AkunOrangTua;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * UC-16 — Provisioning Akun Portal Orang Tua (Otomatis). PRD §9.18, UCIC UC-16.
 *
 * Deteksi duplikasi nomor HP untuk mendukung kasus kakak-adik: satu akun,
 * banyak anak tertaut — TIDAK membuat akun/password baru bila nomor HP sudah
 * terdaftar sebagai akun orang tua manapun.
 */
class ProvisioningAkunOrangTua
{
    public function handle(GenerusDisimpan $event): void
    {
        $generus = $event->generus;
        $nomorHp = $generus->nomor_hp_orang_tua;

        $akun = AkunOrangTua::where('nomor_hp_hash', AkunOrangTua::hashNomorHp($nomorHp))->first();

        if (! $akun) {
            $akun = AkunOrangTua::create([
                'nomor_hp' => $nomorHp,
                'password' => Hash::make(Str::password(8, symbols: false)),
                'must_change_password' => true,
                'is_active' => true,
            ]);
        }

        if (! $akun->generus()->where('generus_id', $generus->id)->exists()) {
            $akun->generus()->attach($generus->id);
        }

        activity('orangtua')
            ->performedOn($generus)
            ->withProperties(['akun_orang_tua_id' => $akun->id, 'nomor_hp' => $nomorHp])
            ->log('Provisioning akun Portal Orang Tua otomatis');
    }
}
