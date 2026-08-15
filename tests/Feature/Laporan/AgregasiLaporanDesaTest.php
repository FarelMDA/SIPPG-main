<?php

namespace Tests\Feature\Laporan;

use App\Livewire\Laporan\GenerateLaporanDesa;
use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\LaporanBulanan;
use App\Models\User;
use App\Services\Laporan\AgregasiLaporanDesa;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AgregasiLaporanDesaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function laporanFinal(Kelompok $kelompok, string $periode, array $sensus, int $persentaseKehadiran): LaporanBulanan
    {
        return LaporanBulanan::create([
            'kelompok_id' => $kelompok->id, 'tingkat' => 'KELOMPOK', 'periode' => $periode, 'versi' => 1,
            'status' => 'FINAL', 'dibuat_oleh' => User::factory()->create()->id,
            'snapshot_data' => [
                'cover' => ['kelompok' => $kelompok->nama, 'periode' => $periode],
                'sensus' => ['per_kategori' => $sensus],
                'kehadiran' => [
                    'persentase_bulan_ini' => $persentaseKehadiran,
                    'tren_6_bulan' => [['periode' => $periode, 'persentase' => $persentaseKehadiran]],
                ],
                'kegiatan' => [['nama' => 'Pengajian', 'tanggal' => $periode.'-10', 'status' => 'TERLAKSANA', 'persentase_kehadiran' => $persentaseKehadiran]],
                'program_monitoring' => [],
            ],
        ]);
    }

    public function test_agregasi_menjumlah_sensus_dan_merata_ratakan_kehadiran_berbobot(): void
    {
        $desa = Desa::factory()->create();
        $kelompokA = Kelompok::factory()->create(['desa_id' => $desa->id]);
        $kelompokB = Kelompok::factory()->create(['desa_id' => $desa->id]);
        $kelompokC = Kelompok::factory()->create(['desa_id' => $desa->id]);

        // A: 20 generus, 100% hadir. B: 5 generus, 0% hadir. C: belum final sama sekali.
        $this->laporanFinal($kelompokA, '2026-08', [['jenjang' => 'ACR', 'laki' => 10, 'perempuan' => 10, 'setempat' => 15, 'pendatang' => 5]], 100);
        $this->laporanFinal($kelompokB, '2026-08', [['jenjang' => 'ACR', 'laki' => 3, 'perempuan' => 2, 'setempat' => 5, 'pendatang' => 0]], 0);

        $hasil = app(AgregasiLaporanDesa::class)->agregasi($desa, '2026-08');

        $this->assertSame(2, $hasil->jumlahKelompokFinal);
        $this->assertSame(3, $hasil->jumlahKelompokTotal);
        $this->assertTrue($hasil->adaYangBelumFinal());
        $this->assertSame([$kelompokC->nama], $hasil->kelompokBelumFinal);

        $baris = collect($hasil->snapshot['sensus']['per_kategori'])->firstWhere('jenjang', 'ACR');
        $this->assertSame(13, $baris['laki']); // 10 + 3
        $this->assertSame(12, $baris['perempuan']); // 10 + 2

        // Rata berbobot: (100*20 + 0*5) / 25 = 80
        $this->assertSame(80, $hasil->snapshot['kehadiran']['persentase_bulan_ini']);

        $this->assertCount(2, $hasil->snapshot['kegiatan']);
        $this->assertSame($kelompokA->nama, $hasil->snapshot['kegiatan'][0]['kelompok']);
    }

    public function test_generate_menampilkan_peringatan_dan_regenerasi_membuat_draft_baru(): void
    {
        $desa = Desa::factory()->create();
        $kelompokFinal = Kelompok::factory()->create(['desa_id' => $desa->id]);
        Kelompok::factory()->create(['desa_id' => $desa->id]); // belum final

        $this->laporanFinal($kelompokFinal, '2026-08', [['jenjang' => 'ACR', 'laki' => 1, 'perempuan' => 1, 'setempat' => 2, 'pendatang' => 0]], 50);

        $pjd = User::factory()->create(['desa_id' => $desa->id]);
        $pjd->assignRole('pjp-desa');

        Livewire::actingAs($pjd, 'web')
            ->test(GenerateLaporanDesa::class)
            ->set('periode', '2026-08')
            ->call('generate');

        $this->assertSame(1, LaporanBulanan::where('desa_id', $desa->id)->where('periode', '2026-08')->count());

        // Regenerasi harus membuat draft baru (versi 2), bukan menimpa draft yang sudah ada (SRS §3.4).
        Livewire::actingAs($pjd, 'web')
            ->test(GenerateLaporanDesa::class)
            ->set('periode', '2026-08')
            ->call('generate');

        $this->assertSame(2, LaporanBulanan::where('desa_id', $desa->id)->where('periode', '2026-08')->count());
        $this->assertNotNull(LaporanBulanan::where('desa_id', $desa->id)->where('periode', '2026-08')->where('versi', 2)->first());
    }
}
