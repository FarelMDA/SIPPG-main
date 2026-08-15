<?php

namespace Tests\Feature\Sensus;

use App\Livewire\Sensus\SensusDashboard;
use App\Models\Jenjang;
use App\Models\Kelompok;
use App\Models\SensusSnapshot;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC-09 — Lihat Sensus. SRS §7, UCIC UC-09.
 */
class SensusDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_bandingkan_periode_menampilkan_dua_periode_bebas(): void
    {
        $kelompok = Kelompok::factory()->create();
        $jenjang = Jenjang::query()->value('kode');

        SensusSnapshot::create([
            'kelompok_id' => $kelompok->id,
            'periode' => '2025-01',
            'jenjang' => $jenjang,
            'status_domisili' => 'SETEMPAT',
            'jenis_kelamin' => 'LAKI',
            'jumlah' => 5,
        ]);

        SensusSnapshot::create([
            'kelompok_id' => $kelompok->id,
            'periode' => '2025-06',
            'jenjang' => $jenjang,
            'status_domisili' => 'SETEMPAT',
            'jenis_kelamin' => 'LAKI',
            'jumlah' => 9,
        ]);

        $pjk = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $pjk->assignRole('pjp-kelompok');

        Livewire::actingAs($pjk, 'web')
            ->test(SensusDashboard::class)
            ->call('bandingkanPeriode', '2025-06', '2025-01')
            ->assertSet('periode', '2025-06')
            ->assertSet('periodeBanding', '2025-01')
            ->assertSet('bulanFilter', 6)
            ->assertSet('tahunFilter', 2025)
            ->assertSet('bulanBanding', 1)
            ->assertSet('tahunBanding', 2025);
    }
}
