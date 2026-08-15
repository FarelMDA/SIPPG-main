<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-01 — Login (guard `web`). SRS §3.1, UCIC UC-01.
 */
#[Layout('layouts.guest')]
class LoginForm extends Component
{
    public string $username = '';

    public string $password = '';

    public function submit(): void
    {
        $this->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = strtolower($this->username).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('username', 'Terlalu banyak percobaan, coba lagi nanti.');

            return;
        }

        $user = \App\Models\User::where('username', $this->username)->first();

        if (! $user || ! \Illuminate\Support\Facades\Hash::check($this->password, $user->password)) {
            RateLimiter::hit($throttleKey, 600);
            $this->addError('username', 'Username/nomor HP atau password salah.');

            return;
        }

        if (! $user->is_active) {
            $this->addError('username', 'Akun tidak aktif, hubungi pengurus.');

            return;
        }

        RateLimiter::clear($throttleKey);
        Auth::guard('web')->login($user);
        // session() (bukan request()->session()) — komponen ini juga dipanggil
        // lewat Livewire::test() terisolasi tanpa StartSession middleware.
        session()->regenerate();

        // Token Sanctum diterbitkan otomatis saat login berhasil (SRS §2.1, §13.2)
        // — dipakai KHUSUS oleh service worker untuk memanggil /api/v1/sync/*,
        // disimpan di sessi agar bisa disuntik ke meta tag & diambil IndexedDB.
        $user->tokens()->where('name', 'sync')->delete();
        $token = $user->createToken('sync')->plainTextToken;
        session(['sync_token' => $token]);

        activity('auth')->causedBy($user)->log('Login berhasil (guard web)');

        $this->redirect($user->must_change_password ? route('password.ganti') : route($user->landingRouteName()), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
