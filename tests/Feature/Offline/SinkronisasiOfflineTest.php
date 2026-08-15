<?php

namespace Tests\Feature\Offline;

use App\Models\Generus;
use App\Models\JenisKegiatan;
use App\Models\Kegiatan;
use App\Models\KegiatanPeserta;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\KurikulumKalender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sinkronisasi Offline presensi & realisasi KBM Reguler — bagian paling rawan
 * bug: idempotent upsert & resolusi konflik last-write-wins. Sejak konvergensi
 * (docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5) menyasar Kegiatan/
 * KegiatanPeserta, bukan lagi Presensi/Jurnal Harian.
 */
class SinkronisasiOfflineTest extends TestCase
{
    use RefreshDatabase;

    private function buatKegiatanKbm(Kelompok $kelompok, Kelas $kelas, User $user): Kegiatan
    {
        $kurikulum = KurikulumKalender::create([
            'jenjang' => $kelas->jenjang->kode,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'jenis' => 'MATERI',
            'item_materi' => ['Tilawati 1 halaman 1'],
            'dibuat_oleh' => $user->id,
        ]);

        $kegiatan = Kegiatan::create([
            'nama' => 'Kegiatan Kelas '.$kelas->nama,
            'tingkat' => 'KELOMPOK',
            'penyelenggara_type' => 'kelompok',
            'penyelenggara_id' => $kelompok->id,
            'jenis_kegiatan_id' => JenisKegiatan::where('nama', 'KBM Reguler')->value('id'),
            'tanggal' => now()->toDateString(),
            'target_tipe' => 'JENJANG_KELAS',
            'status' => 'TERJADWAL',
            'dibuat_oleh' => $user->id,
            'kurikulum_kalender_id' => $kurikulum->id,
            'materi' => $kurikulum->item_materi,
            'realisasi_status' => 'SESUAI_JADWAL',
        ]);

        $kegiatan->targetKelas()->attach($kelas->id);

        return $kegiatan;
    }

    public function test_sync_presensi_idempotent_tidak_duplikat_saat_retry(): void
    {
        $kelompok = Kelompok::factory()->create();
        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        $generus = Generus::factory()->create(['kelas_id' => $kelas->id]);
        $user = User::factory()->create();
        $token = $user->createToken('sync')->plainTextToken;

        $kegiatan = $this->buatKegiatanKbm($kelompok, $kelas, $user);

        $clientUuid = (string) Str::uuid();
        $payload = [
            'entries' => [[
                'client_uuid' => $clientUuid,
                'kegiatan_id' => $kegiatan->id,
                'generus_id' => $generus->id,
                'kelompok_id' => $kelompok->id,
                'kelas_id' => $kelas->id,
                'status' => 'HADIR',
                'updated_at' => now()->toIso8601String(),
            ]],
        ];

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->postJson('/api/v1/sync/presensi', $payload, $headers)->assertOk();
        // Retry (simulasi koneksi putus-nyambung) — tidak boleh membuat baris baru.
        $this->postJson('/api/v1/sync/presensi', $payload, $headers)->assertOk();

        $this->assertSame(1, KegiatanPeserta::where('client_uuid', $clientUuid)->count());
    }

    public function test_sync_bootstrap_mengembalikan_generus_dan_kalender(): void
    {
        $kelompok = Kelompok::factory()->create();
        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        Generus::factory()->count(2)->create(['kelas_id' => $kelas->id]);

        $user = User::factory()->create();
        $this->buatKegiatanKbm($kelompok, $kelas, $user);
        $token = $user->createToken('sync')->plainTextToken;

        $response = $this->getJson("/api/v1/sync/bootstrap?kelasId={$kelas->id}", [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()->assertJsonCount(2, 'generus')->assertJsonCount(1, 'kegiatan_kbm');
    }

    public function test_sync_tanpa_token_ditolak(): void
    {
        $this->postJson('/api/v1/sync/presensi', ['entries' => []])->assertUnauthorized();
    }

    /**
     * Resolusi konflik harus per-field, bukan per baris utuh: kolom yang di server
     * lebih baru tetap dipertahankan, kolom yang di server lebih usang boleh ditimpa
     * entri offline yang datang belakangan — dalam satu payload sync yang sama.
     */
    public function test_sync_realisasi_konflik_diselesaikan_per_field_bukan_per_baris(): void
    {
        $kelompok = Kelompok::factory()->create();
        $kelas = Kelas::factory()->create(['kelompok_id' => $kelompok->id]);
        $user = User::factory()->create();
        $token = $user->createToken('sync')->plainTextToken;

        $kegiatan = $this->buatKegiatanKbm($kelompok, $kelas, $user);
        $kegiatan->update([
            'realisasi_status' => 'SESUAI_JADWAL',
            'realisasi_catatan' => null,
            'field_updated_at' => [
                'realisasi_status' => now()->toIso8601String(),
                'realisasi_catatan' => now()->subDay()->toIso8601String(),
            ],
        ]);

        $payload = [
            'entries' => [[
                'client_uuid' => (string) Str::uuid(),
                'kegiatan_id' => $kegiatan->id,
                'realisasi_status' => 'TIDAK_TERLAKSANA',
                'realisasi_catatan' => 'Alasan libur',
                'updated_at' => now()->subHours(12)->toIso8601String(),
            ]],
        ];

        $response = $this->postJson('/api/v1/sync/realisasi-kegiatan', $payload, ['Authorization' => "Bearer {$token}"]);
        $response->assertOk();

        $kegiatan->refresh();

        // realisasi_status kalah (field ini di server lebih baru) — tetap nilai lama.
        $this->assertSame('SESUAI_JADWAL', $kegiatan->realisasi_status);

        // realisasi_catatan menang (field ini di server lebih usang dari entri offline) — terupdate.
        $this->assertSame('Alasan libur', $kegiatan->realisasi_catatan);

        $this->assertNotEmpty($response->json('conflicts'));
        $this->assertStringContainsString('realisasi_status', $response->json('conflicts.0.message'));
    }
}
