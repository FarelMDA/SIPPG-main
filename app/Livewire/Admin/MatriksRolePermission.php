<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * UC-26 — Kelola Matriks Role & Permission. SRS §5.4, UCIC UC-26.
 * Dibaca langsung dari database (bukan konstanta seeder), supaya otomatis
 * mengikuti role/permission baru yang ditambahkan Fase 2+.
 *
 * Sejak role `sysadmin` ditambahkan: `roles.view` (admin-daerah) tetap read-only;
 * `roles.manage` (sysadmin, satu-satunya pemegang) bisa toggle permission per role
 * langsung dari sini. PERINGATAN OPERASIONAL: perubahan lewat UI ini disimpan ke
 * database, tapi `RolePermissionSeeder::run()` (dijalankan ulang saat deploy)
 * memakai `syncPermissions()` yang MENIMPA balik ke definisi di kode — jadi
 * perubahan lewat UI ini bersifat sementara sampai deploy berikutnya, kecuali
 * juga disalin ke `RolePermissionSeeder.php`.
 */
#[Layout('layouts.app')]
class MatriksRolePermission extends Component
{
    public function mount(): void
    {
        $user = Auth::guard('web')->user();

        abort_unless($user->can('roles.view') || $user->can('roles.manage'), 403);
    }

    /** Role `sysadmin` sengaja tidak bisa diubah lewat UI ini — tetap penuh via kode, mencegah eskalasi/self-lockout. */
    public function togglePermission(int $roleId, int $permissionId): void
    {
        $this->authorize('roles.manage');

        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);

        if ($role->name === 'sysadmin') {
            $this->dispatch('toast', variant: 'danger', message: 'Role sysadmin tidak bisa diubah lewat UI ini.');

            return;
        }

        if ($role->hasPermissionTo($permission)) {
            $role->revokePermissionTo($permission);
            $pesan = "Permission '{$permission->name}' dicabut dari role '{$role->name}'.";
        } else {
            $role->givePermissionTo($permission);
            $pesan = "Permission '{$permission->name}' diberikan ke role '{$role->name}'.";
        }

        activity('roles')->causedBy(Auth::guard('web')->user())->log($pesan);
        $this->dispatch('toast', variant: 'success', message: $pesan);
    }

    public function render()
    {
        return view('livewire.admin.matriks-role-permission', [
            'roles' => Role::with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::orderBy('name')->get(),
            'bisaKelola' => Auth::guard('web')->user()->can('roles.manage'),
        ]);
    }
}
