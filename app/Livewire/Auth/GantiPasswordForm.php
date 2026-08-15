<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

/**
 * UC-02 — Ganti Password (kedua guard). SRS §3.3, UCIC UC-02.
 */
class GantiPasswordForm extends Component
{
    public string $password_lama = '';

    public string $password_baru = '';

    public string $password_konfirmasi = '';

    protected function guardName(): string
    {
        return Auth::guard('orangtua')->check() ? 'orangtua' : 'web';
    }

    public function dipaksa(): bool
    {
        return (bool) Auth::guard($this->guardName())->user()->must_change_password;
    }

    public function submit(): void
    {
        $rules = [
            'password_baru' => ['required', 'string', 'min:8', 'different:password_lama'],
            'password_konfirmasi' => ['required', 'same:password_baru'],
        ];

        if (! $this->dipaksa()) {
            $rules['password_lama'] = ['required', 'string'];
        }

        $this->validate($rules, [
            'password_baru.different' => 'Password baru harus berbeda dari password lama.',
            'password_konfirmasi.same' => 'Konfirmasi password tidak sama.',
        ]);

        $guard = Auth::guard($this->guardName());
        $user = $guard->user();

        if (! $this->dipaksa() && ! Hash::check($this->password_lama, $user->password)) {
            $this->addError('password_lama', 'Password saat ini salah.');

            return;
        }

        $user->forceFill([
            'password' => Hash::make($this->password_baru),
            'must_change_password' => false,
        ])->save();

        activity('auth')->causedBy($user)->log('Password berhasil diubah');

        // Di-flash ke session (bukan dispatch browser event langsung) — event yang
        // ditembak tepat sebelum redirect penuh (navigate: false) hampir selalu
        // "kalah" lomba dengan perpindahan halaman dan tidak sempat tampil.
        // x-flash-toast (dirender di layout tujuan) yang membacanya ulang.
        session()->flash('flash_toast', ['variant' => 'success', 'message' => 'Password berhasil diubah.']);

        $this->redirect($this->guardName() === 'orangtua' ? route('portal.dashboard') : route($user->landingRouteName()), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.ganti-password-form')
            ->layout($this->guardName() === 'orangtua' ? 'layouts.portal' : 'layouts.guest');
    }
}
