<?php

namespace Tests\Feature\Kegiatan;

use App\Livewire\Kegiatan\RekapProgramKegiatan;
use App\Models\Generus;
use App\Models\Kegiatan;
use App\Models\KegiatanPeserta;
use App\Models\KegiatanProgram;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC-40 — Rekap Program Kegiatan (Gabungan Lintas-Tingkat). SRS-Fase-2 §2.8.
 */
class RekapProgramKegiatanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_rekap_menggabungkan_kehadiran_lintas_tingkat_dalam_satu_program(): void
    {
        $kelompokA = Kelompok::factory()->create();
        $kelasA = Kelas::factory()->create(['kelompok_id' => $kelompokA->id]);
        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');

        $program = KegiatanProgram::create(['nama' => 'Program Gabungan', 'tingkat_tertinggi' => 'DESA', 'dibuat_oleh' => $admin->id]);

        $kegiatanKelompok = Kegiatan::create([
            'nama' => 'Sesi Kelompok', 'tingkat' => 'KELOMPOK', 'penyelenggara_type' => 'kelompok', 'penyelenggara_id' => $kelompokA->id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'), 'tanggal' => '2026-08-10', 'status' => 'TERJADWAL', 'kegiatan_program_id' => $program->id, 'dibuat_oleh' => $admin->id,
        ]);

        $generus1 = Generus::factory()->create(['kelas_id' => $kelasA->id]);
        $generus2 = Generus::factory()->create(['kelas_id' => $kelasA->id]);

        KegiatanPeserta::create(['kegiatan_id' => $kegiatanKelompok->id, 'generus_id' => $generus1->id, 'kelompok_id' => $kelompokA->id, 'status_presensi' => 'HADIR']);
        KegiatanPeserta::create(['kegiatan_id' => $kegiatanKelompok->id, 'generus_id' => $generus2->id, 'kelompok_id' => $kelompokA->id, 'status_presensi' => 'ALPHA']);

        $kelompokB = Kelompok::factory()->create();
        $kegiatanDesa = Kegiatan::create([
            'nama' => 'Sesi Desa', 'tingkat' => 'DESA', 'penyelenggara_type' => 'desa', 'penyelenggara_id' => $kelompokB->desa_id,
            'jenis_kegiatan_id' => \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'), 'tanggal' => '2026-08-20', 'status' => 'TERJADWAL', 'kegiatan_program_id' => $program->id, 'dibuat_oleh' => $admin->id,
        ]);
        $generus3 = Generus::factory()->create(['kelas_id' => Kelas::factory()->create(['kelompok_id' => $kelompokB->id])]);
        KegiatanPeserta::create(['kegiatan_id' => $kegiatanDesa->id, 'generus_id' => $generus3->id, 'kelompok_id' => $kelompokB->id, 'status_presensi' => 'HADIR']);

        Livewire::actingAs($admin, 'web')
            ->test(RekapProgramKegiatan::class)
            ->set('kegiatanProgramId', $program->id)
            ->set('periode', '2026-08')
            ->assertViewHas('totalHadir', 2)
            ->assertViewHas('totalPeserta', 3);
    }
}
