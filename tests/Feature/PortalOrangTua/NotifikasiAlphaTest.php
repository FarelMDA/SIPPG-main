<?php

namespace Tests\Feature\PortalOrangTua;

use App\Events\PresensiAlphaDicatat;
use App\Listeners\KirimNotifikasiAlpha;
use App\Models\AkunOrangTua;
use App\Models\Generus;
use App\Models\JenisKegiatan;
use App\Models\Kegiatan;
use App\Models\KegiatanPeserta;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifikasiAlphaTest extends TestCase
{
    use RefreshDatabase;

    public function test_presensi_alpha_membuat_notifikasi_untuk_semua_akun_tertaut(): void
    {
        $kelompok = Kelompok::factory()->create();
        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        $generus = Generus::factory()->create(['kelas_id' => $kelas->id]);
        $user = User::factory()->create();

        $akunAyah = AkunOrangTua::factory()->create();
        $akunIbu = AkunOrangTua::factory()->create();
        $akunAyah->generus()->attach($generus->id);
        $akunIbu->generus()->attach($generus->id);

        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Kelompok',
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id,
            'jenis_kegiatan_id' => JenisKegiatan::where('nama', 'Tambahan')->value('id'),
            'tanggal' => now()->toDateString(),
            'status' => 'TERJADWAL',
            'dibuat_oleh' => $user->id,
        ]);

        $peserta = KegiatanPeserta::create([
            'kegiatan_id' => $kegiatan->id,
            'generus_id' => $generus->id,
            'kelompok_id' => $kelompok->id,
            'kelas_id' => $kelas->id,
            'status_presensi' => 'ALPHA',
            'dicatat_oleh' => $user->id,
        ]);

        (new KirimNotifikasiAlpha)->handle(new PresensiAlphaDicatat($peserta));

        $this->assertDatabaseHas('notifikasi_orang_tua', ['akun_orang_tua_id' => $akunAyah->id, 'generus_id' => $generus->id]);
        $this->assertDatabaseHas('notifikasi_orang_tua', ['akun_orang_tua_id' => $akunIbu->id, 'generus_id' => $generus->id]);
    }
}
