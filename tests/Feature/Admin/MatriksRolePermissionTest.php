<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\MatriksRolePermission;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MatriksRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_sysadmin_bisa_toggle_permission_role_lain(): void
    {
        $sysadmin = User::factory()->create();
        $sysadmin->assignRole('sysadmin');

        $role = Role::findByName('guru', 'web');
        $permission = Permission::findByName('sensus.view', 'web');

        $this->assertFalse($role->hasPermissionTo($permission));

        Livewire::actingAs($sysadmin, 'web')
            ->test(MatriksRolePermission::class)
            ->call('togglePermission', $role->id, $permission->id);

        $this->assertTrue($role->fresh()->hasPermissionTo($permission));
    }

    public function test_sysadmin_tidak_bisa_ubah_role_sysadmin_sendiri(): void
    {
        $sysadmin = User::factory()->create();
        $sysadmin->assignRole('sysadmin');

        $roleSysadmin = Role::findByName('sysadmin', 'web');
        $permission = Permission::findByName('roles.manage', 'web');

        Livewire::actingAs($sysadmin, 'web')
            ->test(MatriksRolePermission::class)
            ->call('togglePermission', $roleSysadmin->id, $permission->id);

        $this->assertTrue($roleSysadmin->fresh()->hasPermissionTo($permission));
    }

    public function test_admin_daerah_hanya_bisa_lihat_tidak_bisa_toggle(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        $role = Role::findByName('guru', 'web');
        $permission = Permission::findByName('sensus.view', 'web');

        Livewire::actingAs($admin, 'web')
            ->test(MatriksRolePermission::class)
            ->assertOk()
            ->call('togglePermission', $role->id, $permission->id)
            ->assertForbidden();
    }

    public function test_role_lain_tidak_bisa_akses_halaman_sama_sekali(): void
    {
        $guru = User::factory()->create();
        $guru->assignRole('guru');

        Livewire::actingAs($guru, 'web')->test(MatriksRolePermission::class)->assertForbidden();
    }
}
