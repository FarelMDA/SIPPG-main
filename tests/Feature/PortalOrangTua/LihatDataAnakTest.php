<?php

namespace Tests\Feature\PortalOrangTua;

use App\Livewire\PortalOrangTua\Dashboard;
use App\Models\AkunOrangTua;
use App\Models\Generus;
use App\Models\Kelas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LihatDataAnakTest extends TestCase
{
    use RefreshDatabase;

    public function test_akun_hanya_melihat_anak_yang_tertaut(): void
    {
        $kelas = Kelas::factory()->create();
        $anakSaya = Generus::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Anak Saya']);
        $anakOrangLain = Generus::factory()->create(['kelas_id' => $kelas->id, 'nama' => 'Anak Orang Lain']);

        $akun = AkunOrangTua::factory()->create();
        $akun->generus()->attach($anakSaya->id);

        Livewire::actingAs($akun, 'orangtua')
            ->test(Dashboard::class)
            ->assertSet('generus_terpilih_id', $anakSaya->id)
            ->assertSee('Anak Saya')
            ->assertDontSee('Anak Orang Lain');
    }

    public function test_pemilih_anak_disembunyikan_jika_hanya_satu_anak(): void
    {
        $kelas = Kelas::factory()->create();
        $anak = Generus::factory()->create(['kelas_id' => $kelas->id]);

        $akun = AkunOrangTua::factory()->create();
        $akun->generus()->attach($anak->id);

        Livewire::actingAs($akun, 'orangtua')
            ->test(Dashboard::class)
            ->assertViewHas('daftarAnak', fn ($daftar) => $daftar->count() === 1);
    }
}
