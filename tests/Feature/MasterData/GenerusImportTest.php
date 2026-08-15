<?php

namespace Tests\Feature\MasterData;

use App\Imports\GenerusImport;
use App\Models\Jenjang;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GenerusImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin-daerah');
        $this->actingAs($admin, 'web');
    }

    /** Kolom "kelas" di template diisi nama Jenjang — resolve jenjang_id & derive kelas_id darinya. */
    public function test_baris_valid_diisi_jenjang_id_dan_kelas_id_terderive(): void
    {
        $kelompok = Kelompok::factory()->create();
        $dasar1 = Jenjang::where('kode', 'DASAR_1')->firstOrFail();
        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id, 'jenjang_id' => $dasar1->id]);

        $import = new GenerusImport;
        $import->collection(new Collection([
            [
                'nama' => 'Fulan bin Fulan',
                'tanggal_lahir' => '2015-01-01',
                'jenis_kelamin' => 'LAKI',
                'kelas' => $dasar1->label,
                'nama_orang_tua' => 'Fulan Ayah',
                'nomor_hp_orang_tua' => '081234567890',
                'status_domisili' => 'SETEMPAT',
            ],
        ]));

        $this->assertSame(1, $import->sukses);
        $this->assertSame([], $import->gagal);
        $this->assertDatabaseHas('generus', [
            'nama' => 'Fulan bin Fulan',
            'jenjang_id' => $dasar1->id,
            'kelas_id' => $kelas->id,
        ]);
    }

    /** Nama Jenjang yang tidak dikenal gagal divalidasi, bukan menyimpan baris dengan kelas_id null. */
    public function test_baris_dengan_nama_jenjang_tidak_dikenal_gagal(): void
    {
        $import = new GenerusImport;
        $import->collection(new Collection([
            [
                'nama' => 'Fulan bin Fulan',
                'tanggal_lahir' => '2015-01-01',
                'jenis_kelamin' => 'LAKI',
                'kelas' => 'Jenjang Tidak Ada',
                'nama_orang_tua' => 'Fulan Ayah',
                'nomor_hp_orang_tua' => '081234567890',
                'status_domisili' => 'SETEMPAT',
            ],
        ]));

        $this->assertSame(0, $import->sukses);
        $this->assertCount(1, $import->gagal);
        $this->assertDatabaseMissing('generus', ['nama' => 'Fulan bin Fulan']);
    }
}
