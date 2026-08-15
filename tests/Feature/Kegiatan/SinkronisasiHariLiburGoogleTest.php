<?php

namespace Tests\Feature\Kegiatan;

use App\Jobs\SinkronisasiHariLiburGoogle;
use App\Models\HariLibur;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * UC-41 — Sinkronisasi Kalender Hari Libur dari Google Calendar. SRS-Fase-2 §2.6.1.
 */
class SinkronisasiHariLiburGoogleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        putenv('GOOGLE_CALENDAR_API_KEY');
    }

    protected function tearDown(): void
    {
        putenv('GOOGLE_CALENDAR_API_KEY');
        parent::tearDown();
    }

    public function test_noop_tanpa_api_key(): void
    {
        Http::fake();

        (new SinkronisasiHariLiburGoogle)->handle();

        Http::assertNothingSent();
        $this->assertDatabaseCount('hari_libur', 0);
    }

    public function test_insert_baris_baru_dari_event_google(): void
    {
        putenv('GOOGLE_CALENDAR_API_KEY=test-key-123');

        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    [
                        'id' => 'gevent-1',
                        'summary' => 'Hari Kemerdekaan',
                        'start' => ['date' => '2026-08-17'],
                        'end' => ['date' => '2026-08-18'], // Google end.date eksklusif
                    ],
                ],
            ]),
        ]);

        (new SinkronisasiHariLiburGoogle)->handle();

        $this->assertDatabaseHas('hari_libur', [
            'google_event_id' => 'gevent-1',
            'nama' => 'Hari Kemerdekaan',
            'sumber' => 'OTOMATIS_GOOGLE',
            'disunting_manual' => false,
        ]);

        $libur = HariLibur::where('google_event_id', 'gevent-1')->first();
        $this->assertSame('2026-08-17', $libur->tanggal_mulai->toDateString());
        $this->assertSame('2026-08-17', $libur->tanggal_selesai->toDateString());
    }

    public function test_baris_existing_disunting_manual_tidak_ditimpa(): void
    {
        putenv('GOOGLE_CALENDAR_API_KEY=test-key-123');

        HariLibur::factory()->create([
            'google_event_id' => 'gevent-2',
            'nama' => 'Nama Lama (sudah diedit admin)',
            'sumber' => 'OTOMATIS_GOOGLE',
            'disunting_manual' => true,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-01-01',
        ]);

        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['id' => 'gevent-2', 'summary' => 'Nama Baru dari Google', 'start' => ['date' => '2026-01-01'], 'end' => ['date' => '2026-01-02']],
                ],
            ]),
        ]);

        (new SinkronisasiHariLiburGoogle)->handle();

        $this->assertDatabaseHas('hari_libur', ['google_event_id' => 'gevent-2', 'nama' => 'Nama Lama (sudah diedit admin)']);
    }

    public function test_baris_yang_sudah_dihapus_tidak_dihidupkan_lagi(): void
    {
        putenv('GOOGLE_CALENDAR_API_KEY=test-key-123');

        $libur = HariLibur::factory()->create([
            'google_event_id' => 'gevent-3',
            'sumber' => 'OTOMATIS_GOOGLE',
            'disunting_manual' => false,
        ]);
        $libur->delete(); // soft-delete

        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['id' => 'gevent-3', 'summary' => 'Coba Hidupkan Lagi', 'start' => ['date' => '2026-01-01'], 'end' => ['date' => '2026-01-02']],
                ],
            ]),
        ]);

        (new SinkronisasiHariLiburGoogle)->handle();

        $this->assertSoftDeleted('hari_libur', ['id' => $libur->id], deletedAtColumn: 'dihapus_pada');
        $this->assertDatabaseMissing('hari_libur', ['google_event_id' => 'gevent-3', 'nama' => 'Coba Hidupkan Lagi']);
    }

    public function test_baris_existing_belum_disunting_diperbarui_bila_data_google_berubah(): void
    {
        putenv('GOOGLE_CALENDAR_API_KEY=test-key-123');

        HariLibur::factory()->create([
            'google_event_id' => 'gevent-4',
            'nama' => 'Nama Awal',
            'sumber' => 'OTOMATIS_GOOGLE',
            'disunting_manual' => false,
            'tanggal_mulai' => '2026-05-01',
            'tanggal_selesai' => '2026-05-01',
        ]);

        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['id' => 'gevent-4', 'summary' => 'Nama Dikoreksi Google', 'start' => ['date' => '2026-05-01'], 'end' => ['date' => '2026-05-02']],
                ],
            ]),
        ]);

        (new SinkronisasiHariLiburGoogle)->handle();

        $this->assertDatabaseHas('hari_libur', ['google_event_id' => 'gevent-4', 'nama' => 'Nama Dikoreksi Google']);
        $this->assertDatabaseCount('hari_libur', 1);
    }

    public function test_kegagalan_api_tidak_melempar_exception(): void
    {
        putenv('GOOGLE_CALENDAR_API_KEY=test-key-123');

        Http::fake([
            'googleapis.com/*' => Http::response(['error' => 'quota exceeded'], 429),
        ]);

        (new SinkronisasiHariLiburGoogle)->handle();

        $this->assertDatabaseCount('hari_libur', 0);
    }
}
