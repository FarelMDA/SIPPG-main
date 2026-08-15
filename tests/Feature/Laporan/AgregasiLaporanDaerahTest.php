<?php

namespace Tests\Feature\Laporan;

use App\Livewire\Laporan\GenerateLaporanDaerah;
use App\Models\Daerah;
use App\Models\Desa;
use App\Models\LaporanBulanan;
use App\Models\User;
use App\Services\Laporan\AgregasiLaporanDaerah;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AgregasiLaporanDaerahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function laporanFinal(Desa $desa, string $periode, array $sensus, int $persentaseKehadiran): LaporanBulanan
    {
        return LaporanBulanan::create([
            'desa_id' => $desa->id, 'tingkat' => 'DESA', 'periode' => $periode, 'versi' => 1,
            'status' => 'FINAL', 'dibuat_oleh' => User::factory()->create()->id,
            'snapshot_data' => [
                'cover' => ['kelompok' => $desa->nama, 'periode' => $periode],
                'sensus' => ['per_kategori' => $sensus],
                'kehadiran' => [
                    'persentase_bulan_ini' => $persentaseKehadiran,
                    'tren_6_bulan' => [['periode' => $periode, 'persentase' => $persentaseKehadiran]],
                ],
                'kegiatan' => [['kelompok' => 'Kelompok A', 'items' => [
                    ['nama' => 'Pengajian Desa', 'tanggal' => $periode.'-10', 'status' => 'TERLAKSANA', 'persentase_kehadiran' => $persentaseKehadiran],
                ]]],
                'program_monitoring' => [],
            ],
        ]);
    }

    public function test_agregasi_menjumlah_sensus_dan_merata_ratakan_kehadiran_berbobot(): void
    {
        $daerah = Daerah::factory()->create();
        $desaA = Desa::factory()->create(['daerah_id' => $daerah->id]);
        $desaB = Desa::factory()->create(['daerah_id' => $daerah->id]);
        $desaC = Desa::factory()->create(['daerah_id' => $daerah->id]);

        // A: 20 generus, 100% hadir. B: 5 generus, 0% hadir. C: belum final sama sekali.
        $this->laporanFinal($desaA, '2026-08', [['jenjang' => 'ACR', 'laki' => 10, 'perempuan' => 10, 'setempat' => 15, 'pendatang' => 5]], 100);
        $this->laporanFinal($desaB, '2026-08', [['jenjang' => 'ACR', 'laki' => 3, 'perempuan' => 2, 'setempat' => 5, 'pendatang' => 0]], 0);

        $hasil = app(AgregasiLaporanDaerah::class)->agregasi($daerah, '2026-08');

        $this->assertSame(2, $hasil->jumlahDesaFinal);
        $this->assertSame(3, $hasil->jumlahDesaTotal);
        $this->assertTrue($hasil->adaYangBelumFinal());
        $this->assertSame([$desaC->nama], $hasil->desaBelumFinal);

        $baris = collect($hasil->snapshot['sensus']['per_kategori'])->firstWhere('jenjang', 'ACR');
        $this->assertSame(13, $baris['laki']); // 10 + 3
        $this->assertSame(12, $baris['perempuan']); // 10 + 2

        // Rata berbobot: (100*20 + 0*5) / 25 = 80
        $this->assertSame(80, $hasil->snapshot['kehadiran']['persentase_bulan_ini']);

        $this->assertCount(2, $hasil->snapshot['kegiatan']);
        $this->assertSame($desaA->nama, $hasil->snapshot['kegiatan'][0]['desa']);
        // Per-Kelompok dari snapshot Desa di-flatten — tingkat Daerah hanya menampilkan per-Desa.
        $this->assertSame('Pengajian Desa', $hasil->snapshot['kegiatan'][0]['items'][0]['nama']);
    }

    public function test_generate_menampilkan_peringatan_dan_regenerasi_membuat_draft_baru(): void
    {
        $daerah = Daerah::factory()->create();
        $desaFinal = Desa::factory()->create(['daerah_id' => $daerah->id]);
        Desa::factory()->create(['daerah_id' => $daerah->id]); // belum final

        $this->laporanFinal($desaFinal, '2026-08', [['jenjang' => 'ACR', 'laki' => 1, 'perempuan' => 1, 'setempat' => 2, 'pendatang' => 0]], 50);

        $admin = User::factory()->adminDaerah()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(GenerateLaporanDaerah::class)
            ->set('periode', '2026-08')
            ->call('generate');

        $this->assertSame(1, LaporanBulanan::where('tingkat', 'DAERAH')->where('periode', '2026-08')->count());

        // Regenerasi harus membuat draft baru (versi 2), bukan menimpa draft yang sudah ada (SRS §3.5).
        Livewire::actingAs($admin, 'web')
            ->test(GenerateLaporanDaerah::class)
            ->set('periode', '2026-08')
            ->call('generate');

        $this->assertSame(2, LaporanBulanan::where('tingkat', 'DAERAH')->where('periode', '2026-08')->count());
        $this->assertNotNull(LaporanBulanan::where('tingkat', 'DAERAH')->where('periode', '2026-08')->where('versi', 2)->first());
    }

    public function test_laporan_daerah_final_tidak_masuk_antrian_approval(): void
    {
        $daerah = Daerah::factory()->create();
        $desa = Desa::factory()->create(['daerah_id' => $daerah->id]);
        $this->laporanFinal($desa, '2026-08', [['jenjang' => 'ACR', 'laki' => 1, 'perempuan' => 1, 'setempat' => 2, 'pendatang' => 0]], 100);

        $admin = User::factory()->adminDaerah()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(GenerateLaporanDaerah::class)
            ->set('periode', '2026-08')
            ->call('generate');

        $laporanDaerah = LaporanBulanan::where('tingkat', 'DAERAH')->firstOrFail();
        $laporanDaerah->update(['status' => 'FINAL', 'difinalisasi_oleh' => $admin->id, 'difinalisasi_pada' => now()]);

        $component = Livewire::actingAs($admin, 'web')->test(\App\Livewire\Laporan\AntrianApprovalLaporan::class);
        $antrian = $component->instance()->antrian;

        $this->assertTrue($antrian->every(fn (LaporanBulanan $l) => $l->tingkat !== 'DAERAH'));
    }
}
