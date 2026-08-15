<?php

namespace Tests\Unit\Models;

use App\Models\Kelompok;
use App\Models\Pendidik;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Memastikan Global Scope kelompok_id (SRS §4.2) tidak bocor lintas Kelompok.
 */
class BelongsToKelompokScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pendidik_hanya_terlihat_di_kelompok_sendiri(): void
    {
        $kelompokA = Kelompok::factory()->create();
        $kelompokB = Kelompok::factory()->create();

        Pendidik::create(['kelompok_id' => $kelompokA->id, 'nama' => 'Ustadz A', 'jenis' => 'MT']);
        Pendidik::create(['kelompok_id' => $kelompokB->id, 'nama' => 'Ustadz B', 'jenis' => 'MT']);

        $userA = User::factory()->create(['kelompok_id' => $kelompokA->id]);
        $this->actingAs($userA, 'web');

        $this->assertCount(1, Pendidik::all());
        $this->assertSame('Ustadz A', Pendidik::first()->nama);
    }

    public function test_admin_daerah_melihat_semua_kelompok(): void
    {
        $kelompokA = Kelompok::factory()->create();
        $kelompokB = Kelompok::factory()->create();

        Pendidik::create(['kelompok_id' => $kelompokA->id, 'nama' => 'Ustadz A', 'jenis' => 'MT']);
        Pendidik::create(['kelompok_id' => $kelompokB->id, 'nama' => 'Ustadz B', 'jenis' => 'MT']);

        $admin = User::factory()->adminDaerah()->create();
        $admin->assignRole('admin-daerah');
        $this->actingAs($admin, 'web');

        $this->assertCount(2, Pendidik::all());
    }
}
