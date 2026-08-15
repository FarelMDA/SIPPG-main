<?php

namespace App\Livewire\Auth;

use App\Models\AkunOrangTua;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-01 — Login (guard `orangtua`), nomor HP sebagai username (PRD §9.18).
 */
#[Layout('layouts.guest')]
class LoginOrangTuaForm extends Component
{
    public string $nomor_hp = '';

    public string $password = '';

    public function submit(): void
    {
        $this->validate([
            'nomor_hp' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = strtolower($this->nomor_hp).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('nomor_hp', 'Terlalu banyak percobaan, coba lagi nanti.');

            return;
        }

        $akun = AkunOrangTua::where('nomor_hp_hash', AkunOrangTua::hashNomorHp($this->nomor_hp))->first();

        if (! $akun || ! Hash::check($this->password, $akun->password)) {
            RateLimiter::hit($throttleKey, 600);
            $this->addError('nomor_hp', 'Username/nomor HP atau password salah.');

            return;
        }

        if (! $akun->is_active) {
            $this->addError('nomor_hp', 'Akun tidak aktif, hubungi pengurus.');

            return;
        }

        RateLimiter::clear($throttleKey);
        Auth::guard('orangtua')->login($akun);
        session()->regenerate();

        activity('auth')->causedBy($akun)->log('Login berhasil (guard orangtua)');

        $this->redirect($akun->must_change_password ? route('portal.password.ganti') : route('portal.dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login-orang-tua-form');
    }
}
