<?php

namespace App\Livewire\Admin;

use App\Models\AkunOrangTua;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC-03 — Reset Password akun Portal Orang Tua. SRS §3.4, UCIC UC-03.
 */
#[Layout('layouts.app')]
class KelolaAkunOrangTua extends Component
{
    use WithPagination;

    public ?string $generatedPassword = null;

    public ?string $generatedForNomorHp = null;

    public function mount(): void
    {
        $this->authorize('orangtua.reset-password');
    }

    /**
     * `whereHas('generus')` sengaja dipakai untuk scoping, bukan filter manual — relasi ini
     * menjalankan query `Generus::query()`, yang otomatis kena Global Scope
     * `kelompokViaKelas` (§4.2, lihat Generus::booted()). Untuk Admin Daerah scope itu
     * bypass (tampil semua akun); untuk PJP Kelompok otomatis terbatas ke akun ortu yang
     * tertaut ke Generus di kelompoknya saja (SRS §3.4/UCIC UC-03).
     */
    public function getAkunProperty()
    {
        return AkunOrangTua::withCount('generus')->whereHas('generus')->orderBy('id')->paginate(10);
    }

    public function resetPassword(int $id): void
    {
        $target = AkunOrangTua::whereHas('generus')->find($id);

        if (! $target) {
            $this->dispatch('toast', variant: 'danger', message: 'Anda tidak berwenang mereset akun ini.');

            return;
        }

        $password = Str::password(8, symbols: false);

        $target->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ])->save();

        DB::table('sessions')->where('user_id', $target->id)->delete();

        activity('orangtua')->causedBy(Auth::guard('web')->user())->performedOn($target)->log('Password akun orang tua direset');

        $this->generatedPassword = $password;
        $this->generatedForNomorHp = $target->nomor_hp;
        $this->dispatch('toast', variant: 'success', message: 'Password berhasil direset. Sesi pengguna tersebut telah berakhir.');
    }

    public function render()
    {
        return view('livewire.admin.kelola-akun-orang-tua');
    }
}
