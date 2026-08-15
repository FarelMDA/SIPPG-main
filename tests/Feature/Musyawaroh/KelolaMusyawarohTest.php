<?php

namespace Tests\Feature\Musyawaroh;

use App\Livewire\Musyawaroh\KelolaMusyawaroh;
use App\Models\Daerah;
use App\Models\Desa;
use App\Models\JenisMusyawaroh;
use App\Models\Kelompok;
use App\Models\Musyawaroh;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaMusyawarohTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_sekretaris_kbm_menyimpan_notulen_tingkat_kelompok(): void
    {
        $kelompok = Kelompok::factory()->create();
        $sekretaris = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $sekretaris->assignRole('sekretaris-kbm');

        Livewire::actingAs($sekretaris, 'web')
            ->test(KelolaMusyawaroh::class)
            ->call('mulaiBaru')
            ->set('tanggal', now()->toDateString())
            ->set('items', [['pokok_masalah' => 'Kehadiran menurun', 'keputusan' => '', 'pic' => '', 'keterangan' => '']])
            ->call('simpan');

        $this->assertDatabaseHas('musyawaroh', [
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id,
            'jenis_musyawaroh_id' => JenisMusyawaroh::where('tingkat', 'KELOMPOK')->where('nama', 'Musyawaroh Pengurus KBM')->value('id'),
        ]);
    }

    public function test_wanbin_desa_menyimpan_notulen_tingkat_desa(): void
    {
        $desa = Desa::factory()->create();
        $wanbin = User::factory()->create(['desa_id' => $desa->id]);
        $wanbin->assignRole('wanbin-desa');

        Livewire::actingAs($wanbin, 'web')
            ->test(KelolaMusyawaroh::class)
            ->call('mulaiBaru')
            ->set('tanggal', now()->toDateString())
            ->set('items', [['pokok_masalah' => 'Koordinasi diklat', 'keputusan' => '', 'pic' => '', 'keterangan' => '']])
            ->call('simpan');

        $this->assertDatabaseHas('musyawaroh', [
            'tingkat' => 'DESA',
            'penyelenggara_type' => 'desa',
            'penyelenggara_id' => $desa->id,
            'jenis_musyawaroh_id' => JenisMusyawaroh::where('tingkat', 'DESA')->where('nama', 'Musyawarah PPD-PPK')->value('id'),
        ]);
    }

    public function test_wanbin_daerah_bisa_mengesahkan_notulen_daerah(): void
    {
        $daerah = Daerah::factory()->create();
        $wanbin = User::factory()->create();
        $wanbin->assignRole('wanbin-daerah');

        Livewire::actingAs($wanbin, 'web')
            ->test(KelolaMusyawaroh::class)
            ->call('mulaiBaru')
            ->set('tanggal', now()->toDateString())
            ->set('items', [['pokok_masalah' => 'Koordinasi PPG-PJP Desa', 'keputusan' => '', 'pic' => '', 'keterangan' => '']])
            ->call('simpan');

        $musyawaroh = Musyawaroh::where('tingkat', 'DAERAH')->firstOrFail();

        Livewire::actingAs($wanbin, 'web')
            ->test(KelolaMusyawaroh::class)
            ->call('sahkan', $musyawaroh->id);

        $this->assertNotNull($musyawaroh->fresh()->disahkan_oleh);
        $this->assertSame($wanbin->id, $musyawaroh->fresh()->disahkan_oleh);
    }

    public function test_sekretaris_kbm_tidak_bisa_lihat_cetak_notulen_kelompok_lain(): void
    {
        $kelompokSendiri = Kelompok::factory()->create();
        $kelompokLain = Kelompok::factory()->create();

        $notulenLain = Musyawaroh::create([
            'kelompok_id' => $kelompokLain->id,
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompokLain->id,
            'jenis_musyawaroh_id' => JenisMusyawaroh::where('tingkat', 'KELOMPOK')->where('nama', 'Musyawaroh Pengurus KBM')->value('id'),
            'tanggal' => now()->toDateString(),
        ]);

        $sekretaris = User::factory()->create(['kelompok_id' => $kelompokSendiri->id]);
        $sekretaris->assignRole('sekretaris-kbm');

        Livewire::actingAs($sekretaris, 'web')
            ->test(KelolaMusyawaroh::class)
            ->call('lihatCetak', $notulenLain->id)
            ->assertSet('viewing', null);
    }

    public function test_sekretaris_ppg_tidak_bisa_mengesahkan_notulen_daerah(): void
    {
        $daerah = Daerah::factory()->create();
        $sekretaris = User::factory()->create();
        $sekretaris->assignRole('sekretaris-ppg');

        Livewire::actingAs($sekretaris, 'web')
            ->test(KelolaMusyawaroh::class)
            ->call('mulaiBaru')
            ->set('tanggal', now()->toDateString())
            ->set('items', [['pokok_masalah' => 'Koordinasi PPG-PJP Desa', 'keputusan' => '', 'pic' => '', 'keterangan' => '']])
            ->call('simpan');

        $musyawaroh = Musyawaroh::where('tingkat', 'DAERAH')->firstOrFail();

        Livewire::actingAs($sekretaris, 'web')
            ->test(KelolaMusyawaroh::class)
            ->call('sahkan', $musyawaroh->id);

        $this->assertNull($musyawaroh->fresh()->disahkan_oleh);
    }
}
