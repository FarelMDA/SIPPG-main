<?php

namespace Tests\Feature\Kegiatan;

use App\Livewire\Kegiatan\KelolaHariLibur;
use App\Models\Generus;
use App\Models\Kegiatan;
use App\Models\KegiatanJadwal;
use App\Models\KegiatanPeserta;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\User;
use App\Services\Kegiatan\GeneratorKegiatanDariJadwal;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC-39 — Sinkronisasi Otomatis Kegiatan vs Hari Libur. SRS-Fase-2 §2.6.
 */
class BatalkanKegiatanKarenaLiburTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(Carbon::parse('2026-08-01'));
    }

    public function test_simpan_hari_libur_membatalkan_kegiatan_mendatang_yang_bertabrakan(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);

        $jadwal = KegiatanJadwal::factory()->create([
            'penyelenggara_id' => $kelompok->id,
            'dibuat_oleh' => $pjk->id,
            'hari_dalam_minggu' => ['SABTU'],
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);
        app(GeneratorKegiatanDariJadwal::class)->generate($jadwal);

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaHariLibur::class)
            ->set('nama', 'Libur Mendadak')
            ->set('tanggalMulai', '2026-08-15')
            ->set('tanggalSelesai', '2026-08-15')
            ->call('simpan');

        $event = Kegiatan::where('kegiatan_jadwal_id', $jadwal->id)->whereDate('tanggal', '2026-08-15')->firstOrFail();

        $this->assertSame('TIDAK_TERLAKSANA', $event->status);
        $this->assertStringContainsString('Libur Mendadak', $event->catatan_status);
    }

    public function test_kegiatan_lampau_tidak_dibatalkan(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);

        $jadwal = KegiatanJadwal::factory()->create([
            'penyelenggara_id' => $kelompok->id,
            'dibuat_oleh' => $pjk->id,
            'hari_dalam_minggu' => ['SABTU'],
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-08-31',
        ]);
        app(GeneratorKegiatanDariJadwal::class)->generate($jadwal);

        // "Hari ini" dibekukan 1 Agustus 2026 (setUp) — 25 Juli 2026 sudah lampau.
        $eventLampau = Kegiatan::where('kegiatan_jadwal_id', $jadwal->id)->whereDate('tanggal', '2026-07-25')->first();
        $this->assertNotNull($eventLampau);

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaHariLibur::class)
            ->set('nama', 'Libur Retroaktif')
            ->set('tanggalMulai', '2026-07-01')
            ->set('tanggalSelesai', '2026-08-31')
            ->call('simpan');

        $this->assertSame('TERJADWAL', $eventLampau->fresh()->status);
    }

    public function test_kegiatan_yang_sudah_ada_presensi_tidak_dibatalkan(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);

        $jadwal = KegiatanJadwal::factory()->create([
            'penyelenggara_id' => $kelompok->id,
            'dibuat_oleh' => $pjk->id,
            'hari_dalam_minggu' => ['SABTU'],
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
        ]);
        app(GeneratorKegiatanDariJadwal::class)->generate($jadwal);

        $event = Kegiatan::where('kegiatan_jadwal_id', $jadwal->id)->whereDate('tanggal', '2026-08-15')->firstOrFail();

        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        $generus = Generus::factory()->create(['kelas_id' => $kelas->id]);
        KegiatanPeserta::create([
            'kegiatan_id' => $event->id,
            'generus_id' => $generus->id,
            'kelompok_id' => $kelompok->id,
            'status_presensi' => 'HADIR',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaHariLibur::class)
            ->set('nama', 'Libur Setelah Presensi')
            ->set('tanggalMulai', '2026-08-15')
            ->set('tanggalSelesai', '2026-08-15')
            ->call('simpan');

        $this->assertSame('TERJADWAL', $event->fresh()->status);
    }

    public function test_kegiatan_insidental_tidak_ikut_dibatalkan_otomatis(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);

        $insidental = Kegiatan::create([
            'nama' => 'Kegiatan Manual',
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'),
            'tanggal' => '2026-08-15',
            'status' => 'TERJADWAL',
            'dibuat_oleh' => $pjk->id,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        Livewire::actingAs($admin, 'web')
            ->test(KelolaHariLibur::class)
            ->set('nama', 'Libur Insidental Test')
            ->set('tanggalMulai', '2026-08-15')
            ->set('tanggalSelesai', '2026-08-15')
            ->call('simpan');

        $this->assertSame('TERJADWAL', $insidental->fresh()->status);
    }
}
