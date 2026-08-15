<?php

namespace Tests\Feature\MasterData;

use App\Livewire\MasterData\KelolaGenerus;
use App\Models\AkunOrangTua;
use App\Models\Generus;
use App\Models\Jenjang;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaGenerusTest extends TestCase
{
    use RefreshDatabase;

    private const NOMOR_HP_ORANG_TUA = '081234567890';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_tambah_generus_baru_memicu_provisioning_akun_orang_tua(): void
    {
        $kelas = Kelas::factory()->create();
        $sekretaris = User::factory()->create(['kelompok_id' => $kelas->kelompok_id]);
        $sekretaris->assignRole('sekretaris-kbm');

        Livewire::actingAs($sekretaris, 'web')
            ->test(KelolaGenerus::class)
            ->set('nama', 'Fulan bin Fulan')
            ->set('tanggal_lahir', '2015-01-01')
            ->set('jenis_kelamin', 'LAKI')
            ->set('jenjang_id', $kelas->jenjang_id)
            ->set('nama_orang_tua', 'Fulan Ayah')
            ->set('nomor_hp_orang_tua', self::NOMOR_HP_ORANG_TUA)
            ->set('status_domisili', 'SETEMPAT')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('generus', ['nama' => 'Fulan bin Fulan']);
        $this->assertDatabaseHas('akun_orang_tua', ['nomor_hp_hash' => AkunOrangTua::hashNomorHp(self::NOMOR_HP_ORANG_TUA)]);

        $akun = AkunOrangTua::where('nomor_hp_hash', AkunOrangTua::hashNomorHp(self::NOMOR_HP_ORANG_TUA))->first();
        $this->assertTrue($akun->must_change_password);
        $this->assertCount(1, $akun->generus);
    }

    public function test_kakak_adik_nomor_hp_sama_tidak_membuat_akun_baru(): void
    {
        $kelas = Kelas::factory()->create();
        $sekretaris = User::factory()->create(['kelompok_id' => $kelas->kelompok_id]);
        $sekretaris->assignRole('sekretaris-kbm');

        $nomorHp = '081234500000';

        Livewire::actingAs($sekretaris, 'web')->test(KelolaGenerus::class)
            ->set('nama', 'Anak Pertama')
            ->set('tanggal_lahir', '2012-01-01')
            ->set('jenis_kelamin', 'LAKI')
            ->set('jenjang_id', $kelas->jenjang_id)
            ->set('nama_orang_tua', 'Bapak Fulan')
            ->set('nomor_hp_orang_tua', $nomorHp)
            ->set('status_domisili', 'SETEMPAT')
            ->call('simpan');

        Livewire::actingAs($sekretaris, 'web')->test(KelolaGenerus::class)
            ->set('nama', 'Anak Kedua')
            ->set('tanggal_lahir', '2014-01-01')
            ->set('jenis_kelamin', 'PEREMPUAN')
            ->set('jenjang_id', $kelas->jenjang_id)
            ->set('nama_orang_tua', 'Bapak Fulan')
            ->set('nomor_hp_orang_tua', $nomorHp)
            ->set('status_domisili', 'SETEMPAT')
            ->call('simpan');

        $this->assertSame(1, AkunOrangTua::where('nomor_hp_hash', AkunOrangTua::hashNomorHp($nomorHp))->count());
        $this->assertCount(2, AkunOrangTua::where('nomor_hp_hash', AkunOrangTua::hashNomorHp($nomorHp))->first()->generus);
    }

    public function test_ubah_nomor_hp_orang_tua_saat_edit_memicu_provisioning_akun_baru(): void
    {
        $kelas = Kelas::factory()->create();
        $sekretaris = User::factory()->create(['kelompok_id' => $kelas->kelompok_id]);
        $sekretaris->assignRole('sekretaris-kbm');

        $generus = Generus::factory()->create(['kelas_id' => $kelas->id, 'nomor_hp_orang_tua' => self::NOMOR_HP_ORANG_TUA]);

        $nomorHpBaru = '089988776655';

        Livewire::actingAs($sekretaris, 'web')
            ->test(KelolaGenerus::class)
            ->call('openEdit', $generus->id)
            ->set('nomor_hp_orang_tua', $nomorHpBaru)
            ->call('simpan');

        $this->assertDatabaseHas('akun_orang_tua', ['nomor_hp_hash' => AkunOrangTua::hashNomorHp($nomorHpBaru)]);

        $akun = AkunOrangTua::where('nomor_hp_hash', AkunOrangTua::hashNomorHp($nomorHpBaru))->first();
        $this->assertTrue($akun->generus->contains('id', $generus->id));
    }

    public function test_edit_tanpa_ubah_nomor_hp_tidak_membuat_akun_baru(): void
    {
        $kelas = Kelas::factory()->create();
        $sekretaris = User::factory()->create(['kelompok_id' => $kelas->kelompok_id]);
        $sekretaris->assignRole('sekretaris-kbm');

        $generus = Generus::factory()->create(['kelas_id' => $kelas->id, 'nomor_hp_orang_tua' => self::NOMOR_HP_ORANG_TUA]);
        AkunOrangTua::factory()->create(['nomor_hp' => self::NOMOR_HP_ORANG_TUA])->generus()->attach($generus->id);

        $jumlahAkunSebelum = AkunOrangTua::count();

        Livewire::actingAs($sekretaris, 'web')
            ->test(KelolaGenerus::class)
            ->call('openEdit', $generus->id)
            ->set('nama', 'Nama Diperbarui')
            ->call('simpan');

        $this->assertSame($jumlahAkunSebelum, AkunOrangTua::count());
    }

    public function test_ubah_status_domisili_tercatat_di_riwayat(): void
    {
        $kelas = Kelas::factory()->create();
        $sekretaris = User::factory()->create(['kelompok_id' => $kelas->kelompok_id]);
        $sekretaris->assignRole('sekretaris-kbm');

        $generus = Generus::factory()->create(['kelas_id' => $kelas->id, 'status_domisili' => 'SETEMPAT']);

        Livewire::actingAs($sekretaris, 'web')
            ->test(KelolaGenerus::class)
            ->call('openEdit', $generus->id)
            ->set('status_domisili', 'PENDATANG')
            ->call('simpan');

        $this->assertDatabaseHas('generus_status_histories', [
            'generus_id' => $generus->id,
            'status_domisili' => 'PENDATANG',
        ]);
    }

    /** Naik kelas pindah Jenjang (bukan pilih Kelas manual lagi) — kelas_id ikut ter-derive. */
    public function test_naik_kelas_memindahkan_jenjang_dan_kelas(): void
    {
        $kelompok = Kelompok::factory()->create();
        $dasar1 = Jenjang::where('kode', 'DASAR_1')->firstOrFail();
        $dasar2 = Jenjang::where('kode', 'DASAR_2')->firstOrFail();

        $kelasLama = Kelas::factory()->create(['kelompok_id' => $kelompok->id, 'jenjang_id' => $dasar1->id, 'status_aktif' => true]);
        $kelasBaru = Kelas::factory()->create(['kelompok_id' => $kelompok->id, 'jenjang_id' => $dasar2->id, 'status_aktif' => true]);

        $generus = Generus::factory()->create(['kelas_id' => $kelasLama->id, 'jenjang_id' => $dasar1->id]);

        $sekretaris = User::factory()->create(['kelompok_id' => $kelompok->id]);
        $sekretaris->assignRole('sekretaris-kbm');

        Livewire::actingAs($sekretaris, 'web')
            ->test(KelolaGenerus::class)
            ->call('openNaikKelas', $generus->id)
            ->set('naikKelasJenjangId', $dasar2->id)
            ->set('naikKelasSemester', '2026-Ganjil')
            ->call('naikkanKelas')
            ->assertHasNoErrors();

        $generus->refresh();
        $this->assertSame($kelasBaru->id, $generus->kelas_id);
        $this->assertSame($dasar2->id, $generus->jenjang_id);
        $this->assertDatabaseHas('generus_kelas_histories', [
            'generus_id' => $generus->id,
            'kelas_id' => $kelasBaru->id,
            'semester' => '2026-Ganjil',
        ]);
    }
}
