<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\KelolaPengguna;
use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaPenggunaRoleExpansionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pjp_desa_bisa_membuat_akun_wanbin_desa_di_desanya_sendiri(): void
    {
        $desa = Desa::factory()->create();
        $pjd = User::factory()->create(['desa_id' => $desa->id]);
        $pjd->assignRole('pjp-desa');

        Livewire::actingAs($pjd, 'web')
            ->test(KelolaPengguna::class)
            ->set('nama', 'Kyai Fulan')
            ->set('username', 'kyai-fulan')
            ->set('role', 'wanbin-desa')
            ->set('desa_id', $desa->id)
            ->call('simpan');

        $this->assertDatabaseHas('users', ['username' => 'kyai-fulan', 'desa_id' => $desa->id]);
        $this->assertTrue(User::where('username', 'kyai-fulan')->first()->hasRole('wanbin-desa'));
    }

    public function test_pjp_desa_tidak_bisa_membuat_akun_wanbin_desa_di_desa_lain(): void
    {
        $desaSendiri = Desa::factory()->create();
        $desaLain = Desa::factory()->create();
        $pjd = User::factory()->create(['desa_id' => $desaSendiri->id]);
        $pjd->assignRole('pjp-desa');

        Livewire::actingAs($pjd, 'web')
            ->test(KelolaPengguna::class)
            ->set('nama', 'Kyai Fulan')
            ->set('username', 'kyai-fulan')
            ->set('role', 'wanbin-desa')
            ->set('desa_id', $desaLain->id)
            ->call('simpan')
            ->assertHasErrors('desa_id');

        $this->assertDatabaseMissing('users', ['username' => 'kyai-fulan']);
    }

    public function test_pjp_desa_tidak_bisa_membuat_akun_role_daerah(): void
    {
        $desa = Desa::factory()->create();
        $pjd = User::factory()->create(['desa_id' => $desa->id]);
        $pjd->assignRole('pjp-desa');

        Livewire::actingAs($pjd, 'web')
            ->test(KelolaPengguna::class)
            ->set('nama', 'Bidang Kurikulum')
            ->set('username', 'bidkur')
            ->set('role', 'bidang-kurikulum')
            ->call('simpan')
            ->assertHasErrors('role');
    }

    public function test_tidak_bisa_buat_koordinator_kedua_untuk_kelompok_yang_sama(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjkPertama = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjkPertama->assignRole('pjp-kelompok');

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaPengguna::class)
            ->set('nama', 'PJP Kelompok Kedua')
            ->set('username', 'pjk-kedua')
            ->set('role', 'pjp-kelompok')
            ->set('kelompok_id', $kelompok->id)
            ->call('simpan')
            ->assertHasErrors('role');

        $this->assertDatabaseMissing('users', ['username' => 'pjk-kedua']);
    }

    public function test_boleh_ada_lebih_dari_satu_sekretaris_kbm_di_kelompok_yang_sama(): void
    {
        $kelompok = Kelompok::factory()->create();
        $sekretarisPertama = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $sekretarisPertama->assignRole('sekretaris-kbm');

        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(KelolaPengguna::class)
            ->set('nama', 'Sekretaris KBM Kedua')
            ->set('username', 'sekretaris-kedua')
            ->set('role', 'sekretaris-kbm')
            ->set('kelompok_id', $kelompok->id)
            ->call('simpan');

        $this->assertDatabaseHas('users', ['username' => 'sekretaris-kedua', 'kelompok_id' => $kelompok->id]);
    }

    public function test_koordinator_lama_yang_dinonaktifkan_tidak_menghalangi_koordinator_baru(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjkLama = User::factory()->create(['kelompok_id' => $kelompok->id, 'is_active' => false]);
        $pjkLama->assignRole('pjp-kelompok');

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaPengguna::class)
            ->set('nama', 'PJP Kelompok Baru')
            ->set('username', 'pjk-baru')
            ->set('role', 'pjp-kelompok')
            ->set('kelompok_id', $kelompok->id)
            ->call('simpan');

        $this->assertDatabaseHas('users', ['username' => 'pjk-baru', 'kelompok_id' => $kelompok->id]);
    }

    public function test_toggle_active_menolak_reaktivasi_koordinator_kedua(): void
    {
        $kelompok = Kelompok::factory()->create();

        $pjkAktif = User::factory()->create(['kelompok_id' => $kelompok->id, 'is_active' => true]);
        $pjkAktif->assignRole('pjp-kelompok');

        $pjkNonaktif = User::factory()->create(['kelompok_id' => $kelompok->id, 'is_active' => false]);
        $pjkNonaktif->assignRole('pjp-kelompok');

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaPengguna::class)
            ->call('toggleActive', $pjkNonaktif->id);

        $this->assertFalse($pjkNonaktif->fresh()->is_active);
    }

    public function test_toggle_active_boleh_reaktivasi_setelah_koordinator_lama_dinonaktifkan(): void
    {
        $kelompok = Kelompok::factory()->create();

        $pjkLama = User::factory()->create(['kelompok_id' => $kelompok->id, 'is_active' => false]);
        $pjkLama->assignRole('pjp-kelompok');

        $pjkBaru = User::factory()->create(['kelompok_id' => $kelompok->id, 'is_active' => false]);
        $pjkBaru->assignRole('pjp-kelompok');

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaPengguna::class)
            ->call('toggleActive', $pjkBaru->id);

        $this->assertTrue($pjkBaru->fresh()->is_active);
    }

    public function test_sysadmin_tidak_muncul_di_daftar_pengguna_admin_daerah(): void
    {
        $sysadmin = User::factory()->create(['nama' => 'Root Sysadmin']);
        $sysadmin->assignRole('sysadmin');

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaPengguna::class)
            ->assertDontSee('Root Sysadmin');
    }

    public function test_admin_daerah_tidak_bisa_reset_password_sysadmin(): void
    {
        $sysadmin = User::factory()->create();
        $sysadmin->assignRole('sysadmin');

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaPengguna::class)
            ->call('resetPassword', $sysadmin->id);

        $this->assertSame($sysadmin->password, $sysadmin->fresh()->password);
    }

    public function test_admin_daerah_tidak_bisa_membuat_akun_sysadmin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaPengguna::class)
            ->set('nama', 'Coba Sysadmin')
            ->set('username', 'coba-sysadmin')
            ->set('role', 'sysadmin')
            ->call('simpan')
            ->assertHasErrors('role');

        $this->assertDatabaseMissing('users', ['username' => 'coba-sysadmin']);
    }
}
