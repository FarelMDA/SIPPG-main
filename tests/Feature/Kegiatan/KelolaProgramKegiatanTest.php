<?php

namespace Tests\Feature\Kegiatan;

use App\Livewire\Kegiatan\FormKegiatan;
use App\Livewire\Kegiatan\KelolaProgramKegiatan;
use App\Models\KegiatanProgram;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC-40 — Kelola Program Kegiatan. SRS-Fase-2 §2.8.
 */
class KelolaProgramKegiatanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_buat_program_baru_menyimpan_dan_memilihnya(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(KelolaProgramKegiatan::class)
            ->set('namaBaru', 'Pengajian APR & AR')
            ->set('tingkatTertinggiBaru', 'DESA')
            ->call('buatProgram')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kegiatan_program', ['nama' => 'Pengajian APR & AR', 'tingkat_tertinggi' => 'DESA']);
    }

    public function test_kegiatan_ditandai_program_via_event_program_dipilih(): void
    {
        $kelompok = Kelompok::factory()->create();
        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        $program = KegiatanProgram::create(['nama' => 'Program X', 'tingkat_tertinggi' => 'KELOMPOK', 'dibuat_oleh' => $pjk->id]);

        Livewire::actingAs($pjk, 'web')
            ->test(FormKegiatan::class)
            ->set('nama', 'Kegiatan Bertanda Program')
            ->set('jenisKegiatanId', \App\Models\JenisKegiatan::where('nama', 'Tambahan')->value('id'))
            ->set('tanggal', now()->toDateString())
            ->call('menerimaProgramDipilih', $program->id)
            ->call('simpan')
            ->assertRedirect(route('kegiatan.index'));

        $this->assertDatabaseHas('kegiatan', ['nama' => 'Kegiatan Bertanda Program', 'kegiatan_program_id' => $program->id]);
    }
}
