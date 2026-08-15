<?php

namespace App\Jobs;

use App\Events\HariLiburDisimpan;
use App\Models\HariLibur;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * UC-41 — Sinkronisasi Kalender Hari Libur dari Google Calendar (bulanan, opsional).
 * SRS-Fase-2 §2.6.1. No-op aman bila GOOGLE_CALENDAR_API_KEY belum dikonfigurasi —
 * seluruh mekanisme Kalender Hari Libur (UC-38) tetap berfungsi penuh tanpa job ini.
 */
class SinkronisasiHariLiburGoogle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const KALENDER_LIBUR_NASIONAL = 'id.indonesian#holiday@group.v.calendar.google.com';

    public function handle(): void
    {
        $apiKey = env('GOOGLE_CALENDAR_API_KEY');

        if (blank($apiKey)) {
            return;
        }

        $events = $this->ambilEventDariGoogle($apiKey);

        if ($events === null) {
            return;
        }

        [$jumlahBaru, $jumlahDiperbarui, $jumlahDilewati] = $this->upsertEvents($events);

        Log::info("SinkronisasiHariLiburGoogle selesai: {$jumlahBaru} baru, {$jumlahDiperbarui} diperbarui, {$jumlahDilewati} dilewati.");
    }

    /** @return array<int, array<string, mixed>>|null null bila panggilan API gagal — proses berhenti aman, dicoba lagi bulan depan. */
    private function ambilEventDariGoogle(string $apiKey): ?array
    {
        try {
            $response = Http::get('https://www.googleapis.com/calendar/v3/calendars/'.rawurlencode(self::KALENDER_LIBUR_NASIONAL).'/events', [
                'key' => $apiKey,
                'timeMin' => now()->startOfYear()->toIso8601String(),
                'timeMax' => now()->addYear()->endOfYear()->toIso8601String(),
                'singleEvents' => 'true',
            ]);
        } catch (Throwable $e) {
            Log::warning('SinkronisasiHariLiburGoogle: exception saat memanggil API — '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            Log::warning('SinkronisasiHariLiburGoogle: API merespons gagal, status '.$response->status());

            return null;
        }

        return $response->json('items', []);
    }

    /** @param  array<int, array<string, mixed>>  $events */
    private function upsertEvents(array $events): array
    {
        $jumlahBaru = 0;
        $jumlahDiperbarui = 0;
        $jumlahDilewati = 0;

        foreach ($events as $event) {
            $data = $this->parseEvent($event);

            if ($data === null) {
                continue;
            }

            $existing = HariLibur::withTrashed()->where('google_event_id', $data['google_event_id'])->first();

            if (! $existing) {
                $libur = HariLibur::create([...$data, 'sumber' => 'OTOMATIS_GOOGLE', 'disunting_manual' => false, 'dibuat_oleh' => null]);
                HariLiburDisimpan::dispatch($libur);
                $jumlahBaru++;

                continue;
            }

            if ($existing->disunting_manual || $existing->trashed()) {
                $jumlahDilewati++;

                continue;
            }

            if ($existing->nama !== $data['nama'] || $existing->tanggal_mulai->toDateString() !== $data['tanggal_mulai'] || $existing->tanggal_selesai->toDateString() !== $data['tanggal_selesai']) {
                $existing->update($data);
                HariLiburDisimpan::dispatch($existing);
                $jumlahDiperbarui++;
            }
        }

        return [$jumlahBaru, $jumlahDiperbarui, $jumlahDilewati];
    }

    /** @return array{google_event_id:string, nama:string, tanggal_mulai:string, tanggal_selesai:string}|null */
    private function parseEvent(array $event): ?array
    {
        $googleEventId = $event['id'] ?? null;
        $nama = $event['summary'] ?? null;
        $tanggalMulai = $event['start']['date'] ?? $event['start']['dateTime'] ?? null;
        $tanggalSelesaiMentah = $event['end']['date'] ?? $event['end']['dateTime'] ?? null;

        if (! $googleEventId || ! $nama || ! $tanggalMulai) {
            return null;
        }

        // Google Calendar: `end.date` pada event sehari-penuh bersifat eksklusif (hari
        // SETELAH event berakhir) — dikurangi 1 hari agar cocok makna tanggal_selesai kita.
        $tanggalSelesai = $tanggalSelesaiMentah
            ? CarbonImmutable::parse($tanggalSelesaiMentah)->subDay()->toDateString()
            : $tanggalMulai;

        return [
            'google_event_id' => $googleEventId,
            'nama' => $nama,
            'tanggal_mulai' => CarbonImmutable::parse($tanggalMulai)->toDateString(),
            'tanggal_selesai' => $tanggalSelesai,
        ];
    }
}
