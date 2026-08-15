<?php

namespace App\Livewire\Admin;

use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC-03 (Reset Password) + UC-07 (Kelola Akun Internal). SRS §3.4/§5.2, UCIC UC-03/UC-07.
 * Sejak ekspansi model-role (Struktur-Organisasi-dan-Role.md §Matriks): 14 role, tiap
 * role scope-nya tetap (Daerah/Desa/Kelompok) — `pjp-desa`/`pjp-kelompok` berperan sebagai
 * Koordinator (maks. 1 akun aktif per lokasi), role lain boleh multi-akun per lokasi.
 */
#[Layout('layouts.app')]
class KelolaPengguna extends Component
{
    use WithPagination;

    /** Scope tiap role — dipakai untuk menentukan field kelompok_id/desa_id mana yang wajib. */
    private const ROLE_SCOPE = [
        'admin-daerah' => 'daerah',
        'wanbin-daerah' => 'daerah',
        'bidang-kurikulum' => 'daerah',
        'bidang-tendik' => 'daerah',
        'sekretaris-ppg' => 'daerah',
        'pakar-pendidik' => 'daerah',
        'pjp-desa' => 'desa',
        'wanbin-desa' => 'desa',
        'sekretaris-desa' => 'desa',
        'pjp-kelompok' => 'kelompok',
        'sekretaris-kbm' => 'kelompok',
        'guru' => 'kelompok',
        'wanbin-kelompok' => 'kelompok',
        'bk-kbm' => 'kelompok',
    ];

    /** Publik — dipakai juga oleh App\Services\Laporan\SusunSnapshotLaporan untuk label "jabatan" Pengurus. */
    public const ROLE_LABELS = [
        'admin-daerah' => 'Admin Daerah',
        'wanbin-daerah' => 'Wanbin Daerah',
        'sekretaris-ppg' => 'Sekretaris PPG',
        'bidang-kurikulum' => 'Bidang Kurikulum',
        'bidang-tendik' => 'Bidang Tenaga Pendidik',
        'pakar-pendidik' => 'Pakar Pendidik',
        'pjp-desa' => 'PJP Desa (Koordinator)',
        'wanbin-desa' => 'Wanbin Desa',
        'sekretaris-desa' => 'Sekretaris PPD (Desa)',
        'pjp-kelompok' => 'PJP Kelompok (Koordinator)',
        'sekretaris-kbm' => 'Sekretaris KBM',
        'guru' => 'Guru',
        'wanbin-kelompok' => 'Wanbin Kelompok',
        'bk-kbm' => 'Bagian BK/Konselor',
    ];

    /** Role yang berperan sebagai Koordinator tunggal per lokasi (Struktur-Organisasi-dan-Role.md, Catatan #1). */
    private const ROLE_KOORDINATOR = ['pjp-desa', 'pjp-kelompok'];

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $nama = '';

    public string $username = '';

    public string $role = '';

    public ?int $kelompok_id = null;

    public ?int $desa_id = null;

    public ?string $generatedPassword = null;

    public ?string $generatedForNama = null;

    public function mount(): void
    {
        $this->authorize('users.manage');
    }

    public static function scopeLevel(string $role): string
    {
        return self::ROLE_SCOPE[$role] ?? 'kelompok';
    }

    private function allowedRoles(): array
    {
        $user = Auth::guard('web')->user();

        if ($user->hasRole('admin-daerah')) {
            return array_keys(self::ROLE_SCOPE);
        }

        if ($user->hasRole('pjp-desa')) {
            // PJP Desa hanya boleh membuat akun setingkat Desa di desanya sendiri (SRS §5.2).
            return ['wanbin-desa', 'sekretaris-desa'];
        }

        // PJP Kelompok hanya boleh membuat akun setingkat/lebih rendah di kelompoknya sendiri (SRS §5.2).
        return ['sekretaris-kbm', 'guru', 'wanbin-kelompok', 'bk-kbm'];
    }

    public function getUsersProperty()
    {
        $user = Auth::guard('web')->user();

        // Akun sysadmin sengaja tidak pernah tampil di sini, termasuk untuk admin-daerah —
        // provisioning & pengelolaannya cuma lewat console (php artisan sysadmin:buat).
        $query = User::query()->with(['kelompok', 'desa'])->whereDoesntHave('roles', fn ($q) => $q->where('name', 'sysadmin'));

        if ($user->hasRole('pjp-desa')) {
            $query->where('desa_id', $user->desa_id);
        } elseif (! $user->hasRole('admin-daerah')) {
            $query->where('kelompok_id', $user->kelompok_id);
        }

        return $query->orderBy('nama')->paginate(10);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'nama', 'username', 'role', 'kelompok_id', 'desa_id']);

        $user = Auth::guard('web')->user();

        if ($user->hasRole('pjp-desa')) {
            $this->desa_id = $user->desa_id;
        } elseif (! $user->hasRole('admin-daerah')) {
            $this->kelompok_id = $user->kelompok_id;
        }

        $this->showFormModal = true;
    }

    /** id target adalah argumen method Livewire — bisa dipanggil dengan id manapun, jadi tetap harus divalidasi cakupannya di server (bukan cuma dari daftar yang sudah difilter di getUsersProperty()). */
    private function targetDalamCakupan(User $target): bool
    {
        // Akun sysadmin tidak bisa diedit/reset/dinonaktifkan lewat UI ini oleh siapa pun,
        // termasuk admin-daerah — id-nya bisa saja disisipkan manual meski tak pernah tampil
        // di getUsersProperty(). Pengelolaan sysadmin cuma lewat console.
        if ($target->hasRole('sysadmin')) {
            return false;
        }

        $user = Auth::guard('web')->user();

        if ($user->hasRole('admin-daerah')) {
            return true;
        }

        if ($user->hasRole('pjp-desa')) {
            return $target->desa_id === $user->desa_id;
        }

        return $target->kelompok_id === $user->kelompok_id;
    }

    public function openEdit(int $id): void
    {
        $target = User::findOrFail($id);

        if (! $this->targetDalamCakupan($target)) {
            $this->dispatch('toast', variant: 'danger', message: 'Anda tidak berwenang mengubah akun ini.');

            return;
        }

        $this->editingId = $target->id;
        $this->nama = $target->nama;
        $this->username = $target->username;
        $this->role = $target->getRoleNames()->first() ?? '';
        $this->kelompok_id = $target->kelompok_id;
        $this->desa_id = $target->desa_id;
        $this->showFormModal = true;
    }

    /** Cegah pjp-kelompok memindahkan/membuat akun di kelompok di luar cakupannya lewat request Livewire yang dimanipulasi (dropdown UI saja tidak cukup, SRS §5.2). */
    private function kelompokDalamCakupan(): bool
    {
        $user = Auth::guard('web')->user();

        if ($user->hasRole('admin-daerah') || ! $this->kelompok_id) {
            return true;
        }

        $kelompokValid = Kelompok::whereKey($this->kelompok_id);

        if ($user->hasRole('pjp-desa')) {
            $kelompokValid->where('desa_id', $user->desa_id);
        } else {
            $kelompokValid->whereKey($user->kelompok_id);
        }

        return $kelompokValid->exists();
    }

    /** Cegah pjp-desa membuat akun Desa-scope di desa di luar desanya sendiri. */
    private function desaDalamCakupan(): bool
    {
        $user = Auth::guard('web')->user();

        if ($user->hasRole('admin-daerah') || ! $this->desa_id) {
            return true;
        }

        return $user->hasRole('pjp-desa') && (int) $this->desa_id === (int) $user->desa_id;
    }

    /** `pjp-desa`/`pjp-kelompok` adalah Koordinator tunggal — maks. 1 akun aktif per desa_id/kelompok_id. */
    private function koordinatorDuplikat(): bool
    {
        return $this->koordinatorDuplikatUntuk($this->role, $this->role === 'pjp-desa' ? $this->desa_id : $this->kelompok_id, $this->editingId);
    }

    /**
     * Sama seperti koordinatorDuplikat(), tapi bisa dipanggil untuk target User yang sudah
     * tersimpan (mis. saat reaktivasi via toggleActive()) — bukan cuma dari state form
     * simpan(). SRS §5.2: reaktivasi akun Koordinator ke-2 di lokasi yang sama harus
     * ditolak sama seperti pembuatan/simpan baru.
     */
    private function koordinatorDuplikatUntuk(string $role, ?int $lokasiId, ?int $kecualiId): bool
    {
        if (! in_array($role, self::ROLE_KOORDINATOR, true)) {
            return false;
        }

        $kolom = $role === 'pjp-desa' ? 'desa_id' : 'kelompok_id';

        return User::role($role)
            ->where($kolom, $lokasiId)
            ->where('is_active', true)
            ->when($kecualiId, fn ($q) => $q->whereKeyNot($kecualiId))
            ->exists();
    }

    public function simpan(): void
    {
        $scopeLevel = self::scopeLevel($this->role);

        $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($this->editingId)],
            'role' => ['required', Rule::in($this->allowedRoles())],
            'kelompok_id' => [$scopeLevel === 'kelompok' ? 'required' : 'nullable', 'nullable', 'exists:kelompok,id'],
            'desa_id' => [$scopeLevel === 'desa' ? 'required' : 'nullable', 'nullable', 'exists:desa,id'],
        ], [
            'username.unique' => 'Username sudah digunakan.',
            'role.in' => 'Anda tidak berwenang membuat akun dengan role ini.',
        ]);

        if ($scopeLevel === 'kelompok' && ! $this->kelompokDalamCakupan()) {
            $this->addError('kelompok_id', 'Kelompok berada di luar cakupan Anda.');

            return;
        }

        if ($scopeLevel === 'desa' && ! $this->desaDalamCakupan()) {
            $this->addError('desa_id', 'Desa berada di luar cakupan Anda.');

            return;
        }

        if ($this->koordinatorDuplikat()) {
            $this->addError('role', 'Sudah ada akun Koordinator aktif dengan role ini di lokasi tsb. Nonaktifkan akun lama terlebih dahulu.');

            return;
        }

        $kelompokId = $scopeLevel === 'kelompok' ? $this->kelompok_id : null;
        $desaId = $scopeLevel === 'desa' ? $this->desa_id : null;

        if ($this->editingId) {
            $target = User::findOrFail($this->editingId);

            // editingId adalah properti publik Livewire — bisa disisipkan lewat request yang
            // dimanipulasi tanpa lewat openEdit(), jadi cakupannya harus dicek ulang di sini juga.
            if (! $this->targetDalamCakupan($target)) {
                $this->addError('editingId', 'Anda tidak berwenang mengubah akun ini.');

                return;
            }

            $target->update([
                'nama' => $this->nama,
                'username' => $this->username,
                'kelompok_id' => $kelompokId,
                'desa_id' => $desaId,
            ]);
            $target->syncRoles([$this->role]);

            activity('users')->causedBy(Auth::guard('web')->user())->performedOn($target)->log('Akun pengguna diperbarui');
            $this->dispatch('toast', variant: 'success', message: 'Perubahan pengguna berhasil disimpan.');
        } else {
            $password = Str::password(8, symbols: false);

            $target = User::create([
                'nama' => $this->nama,
                'username' => $this->username,
                'password' => Hash::make($password),
                'kelompok_id' => $kelompokId,
                'desa_id' => $desaId,
                'must_change_password' => true,
                'is_active' => true,
            ]);
            $target->assignRole($this->role);

            activity('users')->causedBy(Auth::guard('web')->user())->performedOn($target)->log('Akun pengguna dibuat');

            $this->generatedPassword = $password;
            $this->generatedForNama = $target->nama;
            $this->dispatch('toast', variant: 'success', message: 'Pengguna berhasil dibuat.');
        }

        $this->showFormModal = false;
    }

    public function resetPassword(int $id): void
    {
        $target = User::findOrFail($id);

        if (! $this->targetDalamCakupan($target)) {
            $this->dispatch('toast', variant: 'danger', message: 'Anda tidak berwenang mereset akun ini.');

            return;
        }

        $password = Str::password(8, symbols: false);

        $target->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ])->save();

        // Reset password merevoke seluruh sesi aktif akun tsb (SRS §3.4).
        DB::table('sessions')->where('user_id', $target->id)->delete();

        activity('users')->causedBy(Auth::guard('web')->user())->performedOn($target)->log('Password direset oleh admin/PJP');

        $this->generatedPassword = $password;
        $this->generatedForNama = $target->nama;
        $this->dispatch('toast', variant: 'success', message: 'Password berhasil direset. Sesi pengguna tersebut telah berakhir.');
    }

    public function toggleActive(int $id): void
    {
        $target = User::findOrFail($id);

        if (! $this->targetDalamCakupan($target)) {
            $this->dispatch('toast', variant: 'danger', message: 'Anda tidak berwenang mengubah status akun ini.');

            return;
        }

        $mengaktifkan = ! $target->is_active;

        if ($mengaktifkan) {
            $targetRole = $target->getRoleNames()->first() ?? '';
            $lokasiId = $targetRole === 'pjp-desa' ? $target->desa_id : $target->kelompok_id;

            if ($this->koordinatorDuplikatUntuk($targetRole, $lokasiId, $target->id)) {
                $this->dispatch('toast', variant: 'danger', message: 'Sudah ada akun Koordinator aktif dengan role ini di lokasi tsb. Nonaktifkan akun lama terlebih dahulu.');

                return;
            }
        }

        $target->update(['is_active' => $mengaktifkan]);

        activity('users')->causedBy(Auth::guard('web')->user())->performedOn($target)->log('Status aktif akun diubah');
    }

    public function render()
    {
        $user = Auth::guard('web')->user();

        $kelompokOptions = match (true) {
            $user->hasRole('admin-daerah') => Kelompok::orderBy('nama')->pluck('nama', 'id'),
            $user->hasRole('pjp-desa') => Kelompok::where('desa_id', $user->desa_id)->orderBy('nama')->pluck('nama', 'id'),
            default => Kelompok::whereKey($user->kelompok_id)->pluck('nama', 'id'),
        };

        $desaOptions = match (true) {
            $user->hasRole('admin-daerah') => Desa::orderBy('nama')->pluck('nama', 'id'),
            $user->hasRole('pjp-desa') => Desa::whereKey($user->desa_id)->pluck('nama', 'id'),
            default => collect(),
        };

        $allowedRoles = $this->allowedRoles();

        return view('livewire.admin.kelola-pengguna', [
            'kelompokOptions' => $kelompokOptions,
            'desaOptions' => $desaOptions,
            'roleOptions' => array_combine($allowedRoles, array_map(fn ($r) => self::ROLE_LABELS[$r] ?? $r, $allowedRoles)),
            'scopeLevels' => self::ROLE_SCOPE,
        ]);
    }
}
