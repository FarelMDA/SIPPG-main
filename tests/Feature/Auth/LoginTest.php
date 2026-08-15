<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\LoginForm;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_berhasil_redirect_ke_ganti_password_jika_wajib(): void
    {
        $user = User::factory()->create([
            'username' => 'guru1',
            'password' => Hash::make('rahasia123'),
            'must_change_password' => true,
        ]);

        Livewire::test(LoginForm::class)
            ->set('username', 'guru1')
            ->set('password', 'rahasia123')
            ->call('submit')
            ->assertRedirect(route('password.ganti'));

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_login_berhasil_redirect_ke_dashboard_jika_tidak_wajib_ganti_password(): void
    {
        User::factory()->create([
            'username' => 'guru2',
            'password' => Hash::make('rahasia123'),
            'must_change_password' => false,
        ]);

        Livewire::test(LoginForm::class)
            ->set('username', 'guru2')
            ->set('password', 'rahasia123')
            ->call('submit')
            ->assertRedirect(route('dashboard'));
    }

    public function test_login_pjp_desa_redirect_ke_struktur_organisasi_bukan_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $pjd = User::factory()->create([
            'username' => 'pjpdesa1',
            'password' => Hash::make('rahasia123'),
            'must_change_password' => false,
        ]);
        $pjd->assignRole('pjp-desa');

        Livewire::test(LoginForm::class)
            ->set('username', 'pjpdesa1')
            ->set('password', 'rahasia123')
            ->call('submit')
            ->assertRedirect(route('master-data.struktur-organisasi'));
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        User::factory()->create(['username' => 'guru3', 'password' => Hash::make('benar123')]);

        Livewire::test(LoginForm::class)
            ->set('username', 'guru3')
            ->set('password', 'salah123')
            ->call('submit')
            ->assertHasErrors('username');

        $this->assertGuest('web');
    }

    public function test_login_gagal_jika_akun_tidak_aktif(): void
    {
        User::factory()->create([
            'username' => 'guru4',
            'password' => Hash::make('rahasia123'),
            'is_active' => false,
        ]);

        Livewire::test(LoginForm::class)
            ->set('username', 'guru4')
            ->set('password', 'rahasia123')
            ->call('submit')
            ->assertHasErrors('username');

        $this->assertGuest('web');
    }
}
