<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\GantiPasswordForm;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class GantiPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_pjp_desa_redirect_ke_struktur_organisasi_setelah_ganti_password(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $pjd = User::factory()->create(['must_change_password' => true]);
        $pjd->assignRole('pjp-desa');
        $this->actingAs($pjd, 'web');

        Livewire::test(GantiPasswordForm::class)
            ->set('password_baru', 'passwordBaru123')
            ->set('password_konfirmasi', 'passwordBaru123')
            ->call('submit')
            ->assertRedirect(route('master-data.struktur-organisasi'));
    }

    public function test_wajib_ganti_password_tidak_perlu_password_lama(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);
        $this->actingAs($user, 'web');

        Livewire::test(GantiPasswordForm::class)
            ->set('password_baru', 'passwordBaru123')
            ->set('password_konfirmasi', 'passwordBaru123')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('passwordBaru123', $user->fresh()->password));
    }

    public function test_ganti_password_sukarela_wajib_password_lama_benar(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('lama12345'),
            'must_change_password' => false,
        ]);
        $this->actingAs($user, 'web');

        Livewire::test(GantiPasswordForm::class)
            ->set('password_lama', 'salah-password')
            ->set('password_baru', 'passwordBaru123')
            ->set('password_konfirmasi', 'passwordBaru123')
            ->call('submit')
            ->assertHasErrors('password_lama');
    }

    public function test_password_baru_harus_beda_dari_lama(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);
        $this->actingAs($user, 'web');

        Livewire::test(GantiPasswordForm::class)
            ->set('password_baru', 'short')
            ->set('password_konfirmasi', 'short')
            ->call('submit')
            ->assertHasErrors('password_baru');
    }

    public function test_konfirmasi_tidak_sama_gagal(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);
        $this->actingAs($user, 'web');

        Livewire::test(GantiPasswordForm::class)
            ->set('password_baru', 'passwordBaru123')
            ->set('password_konfirmasi', 'beda123456')
            ->call('submit')
            ->assertHasErrors('password_konfirmasi');
    }

    /**
     * Livewire::test() memanggil komponen langsung, TANPA lewat middleware
     * stack HTTP asli — jadi tidak akan menangkap bug di
     * EnsurePasswordChanged yang memblokir endpoint AJAX Livewire sendiri
     * (route name "default.livewire.update", bukan "password.ganti").
     * Test ini sengaja lewat router sungguhan untuk memastikan itu.
     */
    public function test_endpoint_livewire_update_tidak_diblokir_saat_wajib_ganti_password(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);
        $this->actingAs($user, 'web');

        // Halaman lain (selain ganti-password) tetap harus diblokir & diarahkan balik.
        $this->get('/dashboard')->assertRedirect(route('password.ganti'));

        // Endpoint AJAX Livewire TIDAK boleh ikut diarahkan balik ke password.ganti —
        // kalau masih bug, response ini akan berupa redirect 302 ke password.ganti.
        $response = $this->post('/livewire/update', ['fingerprint' => [], 'serverMemo' => [], 'updates' => []]);
        $this->assertNotEquals(302, $response->getStatusCode(), 'Endpoint livewire/update tidak boleh diarahkan balik ke halaman ganti password.');
    }
}
