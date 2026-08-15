<?php

namespace Tests\Feature\Auth;

use App\Livewire\Admin\KelolaPengguna;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_daerah_bisa_reset_password_pengguna(): void
    {
        $admin = User::factory()->adminDaerah()->create();
        $admin->assignRole('admin-daerah');

        $target = User::factory()->create(['must_change_password' => false]);

        Livewire::actingAs($admin, 'web')
            ->test(KelolaPengguna::class)
            ->call('resetPassword', $target->id)
            ->assertSet('generatedPassword', fn ($password) => strlen($password) === 8);

        $this->assertTrue($target->fresh()->must_change_password);
    }

    public function test_pjp_kelompok_tidak_bisa_reset_akun_kelompok_lain(): void
    {
        $kelompokSaya = Kelompok::factory()->create();
        $kelompokLain = Kelompok::factory()->create();

        $pjk = User::factory()->create(['kelompok_id' => $kelompokSaya->id]);
        $pjk->assignRole('pjp-kelompok');

        $target = User::factory()->create(['kelompok_id' => $kelompokLain->id]);

        Livewire::actingAs($pjk, 'web')
            ->test(KelolaPengguna::class)
            ->call('resetPassword', $target->id);

        $this->assertTrue($target->fresh()->must_change_password === false);
    }
}
