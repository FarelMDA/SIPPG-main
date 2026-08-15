<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\KelolaAkunOrangTua;
use App\Models\AkunOrangTua;
use App\Models\Generus;
use App\Models\Kelas;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC-03 — Reset Password oleh Admin (akun Portal Orang Tua). SRS §3.4, UCIC UC-03.
 */
class KelolaAkunOrangTuaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pjp_kelompok_hanya_melihat_akun_ortu_kelompoknya_sendiri(): void
    {
        $kelasSendiri = Kelas::factory()->create();
        $kelasLain = Kelas::factory()->create();

        $generusSendiri = Generus::factory()->create(['kelas_id' => $kelasSendiri->id]);
        $generusLain = Generus::factory()->create(['kelas_id' => $kelasLain->id]);

        $akunSendiri = AkunOrangTua::factory()->create(['nomor_hp' => '081111111111']);
        $akunSendiri->generus()->attach($generusSendiri->id);

        $akunLain = AkunOrangTua::factory()->create(['nomor_hp' => '082222222222']);
        $akunLain->generus()->attach($generusLain->id);

        $pjk = User::factory()->create(['kelompok_id' => $kelasSendiri->kelompok_id]);
        $pjk->assignRole('pjp-kelompok');

        $akun = Livewire::actingAs($pjk, 'web')->test(KelolaAkunOrangTua::class)->get('akun');

        $this->assertTrue($akun->contains('id', $akunSendiri->id));
        $this->assertFalse($akun->contains('id', $akunLain->id));
    }

    public function test_pjp_kelompok_tidak_bisa_reset_akun_ortu_kelompok_lain(): void
    {
        $kelasLain = Kelas::factory()->create();
        $generusLain = Generus::factory()->create(['kelas_id' => $kelasLain->id]);

        $akunLain = AkunOrangTua::factory()->create();
        $akunLain->generus()->attach($generusLain->id);
        $passwordLama = $akunLain->password;

        $kelasSendiri = Kelas::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelasSendiri->kelompok_id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(KelolaAkunOrangTua::class)
            ->call('resetPassword', $akunLain->id);

        $this->assertSame($passwordLama, $akunLain->fresh()->password);
    }

    public function test_pjp_kelompok_bisa_reset_akun_ortu_kelompoknya_sendiri(): void
    {
        $kelas = Kelas::factory()->create();
        $generus = Generus::factory()->create(['kelas_id' => $kelas->id]);

        $akun = AkunOrangTua::factory()->create();
        $akun->generus()->attach($generus->id);
        $passwordLama = $akun->password;

        $pjk = User::factory()->create(['kelompok_id' => $kelas->kelompok_id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(KelolaAkunOrangTua::class)
            ->call('resetPassword', $akun->id);

        $this->assertNotSame($passwordLama, $akun->fresh()->password);
        $this->assertTrue($akun->fresh()->must_change_password);
    }
}
