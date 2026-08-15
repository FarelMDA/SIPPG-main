<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuatSysadminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_command_membuat_akun_sysadmin(): void
    {
        $this->artisan('sysadmin:buat', ['nama' => 'Root Sysadmin', 'username' => 'root-sysadmin'])
            ->assertExitCode(0);

        $user = User::where('username', 'root-sysadmin')->firstOrFail();

        $this->assertTrue($user->hasRole('sysadmin'));
        $this->assertNull($user->kelompok_id);
        $this->assertNull($user->desa_id);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->can('roles.manage'));
    }

    public function test_command_gagal_jika_username_sudah_dipakai(): void
    {
        User::factory()->create(['username' => 'sudah-ada']);

        $this->artisan('sysadmin:buat', ['nama' => 'Root Sysadmin', 'username' => 'sudah-ada'])
            ->assertExitCode(1);

        $this->assertSame(1, User::where('username', 'sudah-ada')->count());
    }
}
