<?php

namespace App\Livewire\Kegiatan;

use App\Models\Generus;
use App\Models\Kegiatan;
use App\Models\KegiatanPetugasPresensi;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-22 — Kelola Petugas Presensi Kegiatan. SRS §18.2, UCIC UC-22.
 */
#[Layout('layouts.app')]
class KelolaPetugasPresensi extends Component
{
    public Kegiatan $kegiatan;

    public string $tipe_petugas = 'AKUN_INTERNAL';

    public ?int $user_id = null;

    public ?int $generus_id = null;

    public ?string $tautanBaru = null;

    /**
     * Kelompok yang sedang ditunjukkan petugasnya. Untuk PJP Kelompok/Sekretaris KBM
     * (aktor utama UC-22) ini selalu kelompok_id akun mereka sendiri — tidak ada
     * pilihan lain. UCIC UC-22 tidak menyebut Admin Daerah sebagai aktor, tapi
     * RolePermissionSeeder tetap memberi admin-daerah semua permission (termasuk
     * kegiatan-petugas.manage) sebagai override — untuk kasus itu, admin-daerah wajib
     * memilih salah satu Kelompok dalam cakupan Kegiatan ini secara eksplisit lewat
     * $kelompokCakupanId, bukan diam-diam null (baris kelompok_id=null yang orphan).
     */
    public ?int $kelompokCakupanId = null;

    public function mount(Kegiatan $kegiatan): void
    {
        $this->authorize('kegiatan-petugas.manage');

        $user = Auth::guard('web')->user();
        $this->kegiatan = $kegiatan;

        if ($user->hasRole('admin-daerah')) {
            $this->kelompokCakupanId = $kegiatan->kelompokCakupan()->orderBy('nama')->value('id');

            abort_if($this->kelompokCakupanId === null, 403, 'Tidak ada Kelompok dalam cakupan Kegiatan ini.');

            return;
        }

        // Kelompok pemegang akun harus benar-benar termasuk cakupan kegiatan ini
        // (SRS §18.1 kelompokCakupan()) — tanpa ini, kelompok dari desa/kegiatan lain
        // bisa menunjuk petugas presensinya sendiri untuk kegiatan yang bukan urusannya.
        abort_unless(
            $user->kelompok_id && $kegiatan->kelompokCakupan()->where('id', $user->kelompok_id)->exists(),
            403
        );

        $this->kelompokCakupanId = $user->kelompok_id;
    }

    /** kelompokCakupanId adalah properti publik Livewire (dropdown admin-daerah) — validasi ulang di server. */
    public function updatedKelompokCakupanId(): void
    {
        $user = Auth::guard('web')->user();

        if ($user->hasRole('admin-daerah')) {
            if ($this->kegiatan->kelompokCakupan()->whereKey($this->kelompokCakupanId)->doesntExist()) {
                $this->kelompokCakupanId = $this->kegiatan->kelompokCakupan()->orderBy('nama')->value('id');
            }

            return;
        }

        // Non admin-daerah tidak boleh mengubah kelompokCakupanId sama sekali.
        $this->kelompokCakupanId = $user->kelompok_id;
    }

    public function getPetugasListProperty()
    {
        return KegiatanPetugasPresensi::with(['user', 'generus'])
            ->where('kegiatan_id', $this->kegiatan->id)
            ->where('kelompok_id', $this->kelompokCakupanId)
            ->get();
    }

    public function tunjuk(): void
    {
        if ($this->kegiatan->tingkat === 'KELOMPOK') {
            $this->dispatch('toast', variant: 'danger', message: 'Kegiatan tingkat Kelompok tidak memerlukan penunjukan Petugas Presensi terpisah.');

            return;
        }

        $this->validate([
            'tipe_petugas' => ['required', 'in:AKUN_INTERNAL,GENERUS'],
            'user_id' => [$this->tipe_petugas === 'AKUN_INTERNAL' ? 'required' : 'nullable', 'nullable', 'exists:users,id'],
            'generus_id' => [$this->tipe_petugas === 'GENERUS' ? 'required' : 'nullable', 'nullable', 'exists:generus,id'],
        ]);

        $user = Auth::guard('web')->user();

        // user_id/generus_id juga properti publik Livewire — pastikan tetap dalam kelompok
        // yang sedang aktif (kelompokCakupanId), dropdown di render() sudah discope tapi
        // ini jaga-jaga server-side.
        if ($this->tipe_petugas === 'AKUN_INTERNAL' && $this->user_id
            && User::whereKey($this->user_id)->where('kelompok_id', $this->kelompokCakupanId)->doesntExist()) {
            $this->addError('user_id', 'Akun berada di luar kelompok Anda.');

            return;
        }

        if ($this->tipe_petugas === 'GENERUS' && $this->generus_id
            && Generus::withoutGlobalScopes()->whereKey($this->generus_id)->whereHas('kelas', fn ($q) => $q->where('kelompok_id', $this->kelompokCakupanId))->doesntExist()) {
            $this->addError('generus_id', 'Generus berada di luar kelompok Anda.');

            return;
        }

        $data = [
            'kegiatan_id' => $this->kegiatan->id,
            'kelompok_id' => $this->kelompokCakupanId,
            'ditugaskan_oleh' => $user->id,
        ];

        if ($this->tipe_petugas === 'AKUN_INTERNAL') {
            $data['user_id'] = $this->user_id;
            $this->tautanBaru = null;
        } else {
            $data['generus_id'] = $this->generus_id;
            $data['token'] = (string) Str::uuid();
            $data['token_kedaluwarsa'] = $this->kegiatan->tanggal->copy()->addDay();
        }

        KegiatanPetugasPresensi::create($data);

        activity('kegiatan')->causedBy($user)->log('Petugas Presensi Kegiatan ditunjuk');

        if ($this->tipe_petugas === 'GENERUS') {
            $this->tautanBaru = route('kegiatan.presensi.token', $data['token']);
            $this->dispatch('toast', variant: 'success', message: 'Petugas Presensi ditunjuk. Salin dan sampaikan tautan berikut secara manual.');
        } else {
            $this->dispatch('toast', variant: 'success', message: 'Petugas Presensi berhasil ditunjuk.');
        }

        $this->reset(['user_id', 'generus_id']);
    }

    public function cabut(int $id): void
    {
        $petugas = KegiatanPetugasPresensi::where('kelompok_id', $this->kelompokCakupanId)->findOrFail($id);
        $petugas->delete();

        activity('kegiatan')->causedBy(Auth::guard('web')->user())->log('Penugasan Petugas Presensi dicabut');
        $this->dispatch('toast', variant: 'success', message: 'Penugasan Petugas Presensi berhasil dicabut.');
    }

    public function render()
    {
        $kelompokId = $this->kelompokCakupanId;
        $user = Auth::guard('web')->user();

        return view('livewire.kegiatan.kelola-petugas-presensi', [
            'userOptions' => User::where('kelompok_id', $kelompokId)->whereIn('id', function ($q) {
                $q->select('model_id')->from('model_has_roles')->whereIn('role_id', function ($q2) {
                    $q2->select('id')->from('roles')->whereIn('name', ['sekretaris-kbm', 'guru', 'pjp-kelompok']);
                });
            })->pluck('nama', 'id'),
            'generusOptions' => Generus::withoutGlobalScopes()->whereHas('kelas', fn ($q) => $q->where('kelompok_id', $kelompokId))->pluck('nama', 'id'),
            // Cuma admin-daerah yang perlu memilih Kelompok mana yang sedang ditunjuk
            // petugasnya (bukan aktor utama UC-22 — lihat catatan di properti kelompokCakupanId).
            'kelompokCakupanOptions' => $user->hasRole('admin-daerah')
                ? $this->kegiatan->kelompokCakupan()->orderBy('nama')->pluck('nama', 'id')
                : collect(),
        ]);
    }
}
