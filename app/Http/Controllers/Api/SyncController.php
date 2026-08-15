<?php

namespace App\Http\Controllers\Api;

use App\Events\PresensiAlphaDicatat;
use App\Http\Controllers\Controller;
use App\Models\Generus;
use App\Models\Kegiatan;
use App\Models\KegiatanPeserta;
use App\Models\Kelas;
use App\Models\StatusPresensi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Sinkronisasi Offline untuk presensi & realisasi KBM Reguler (Kegiatan ber-
 * kurikulum_kalender_id) — sebelum konvergensi menyasar Presensi/Jurnal Harian,
 * kini menyasar Kegiatan/KegiatanPeserta (docs/Visi-Konvergensi-Kurikulum-Kegiatan-
 * Presensi.md §5). Satu-satunya kontrak JSON murni di sistem ini — dipakai oleh
 * service worker untuk mengirim draft yang dibuat saat offline (Sanctum token,
 * bukan session).
 */
class SyncController extends Controller
{
    public function bootstrap(Request $request)
    {
        $kelasId = $request->query('kelasId');
        Kelas::findOrFail($kelasId);

        $generus = Generus::withoutGlobalScopes()
            ->where('kelas_id', $kelasId)
            ->where('status_aktif', true)
            ->get(['id', 'nama', 'status_domisili']);

        // Kejadian KBM ±7 hari — cukup untuk isi presensi yang terlewat saat offline
        // maupun beberapa hari ke depan sebelum koneksi kembali normal.
        $kegiatanKbm = Kegiatan::whereNotNull('kurikulum_kalender_id')
            ->whereHas('targetKelas', fn ($q) => $q->where('kelas.id', $kelasId))
            ->whereDate('tanggal', '>=', now()->subDays(7)->toDateString())
            ->orderBy('tanggal')
            ->get(['id', 'tanggal', 'materi', 'realisasi_status', 'realisasi_catatan']);

        return response()->json([
            'generus' => $generus,
            'kegiatan_kbm' => $kegiatanKbm,
        ]);
    }

    public function presensi(Request $request)
    {
        $data = $request->validate([
            'entries' => ['required', 'array'],
            'entries.*.client_uuid' => ['required', 'uuid'],
            'entries.*.kegiatan_id' => ['required', 'integer', 'exists:kegiatan,id'],
            'entries.*.generus_id' => ['required', 'integer', 'exists:generus,id'],
            'entries.*.kelompok_id' => ['required', 'integer', 'exists:kelompok,id'],
            'entries.*.kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'entries.*.status' => ['required', Rule::in(StatusPresensi::pluck('kode'))],
            'entries.*.updated_at' => ['required', 'date'],
        ]);

        $synced = [];
        $conflicts = [];

        foreach ($data['entries'] as $entry) {
            $existing = KegiatanPeserta::where('kegiatan_id', $entry['kegiatan_id'])
                ->where('generus_id', $entry['generus_id'])
                ->first();

            [$merged, $conflictedFields] = $this->resolveFieldsLastWriteWins(
                $existing,
                ['status_presensi' => $entry['status']],
                Carbon::parse($entry['updated_at'])
            );

            if ($conflictedFields !== []) {
                // Last-write-wins PER FIELD berdasar updated_at — kolom ini "kalah"
                // karena field_updated_at server lebih baru dari klien.
                $conflicts[] = [
                    'client_uuid' => $entry['client_uuid'],
                    'resolved_field_winner' => $existing->field_updated_at['status_presensi'] ?? $existing->updated_at->toIso8601String(),
                    'message' => "Presensi {$existing->generus->nama} sudah diperbarui oleh pengguna lain, silakan cek kembali",
                ];
            }

            // client_uuid/dicatat_oleh cuma ikut ditulis kalau minimal satu field
            // beneran menang — baris yang seluruh field-nya kalah dibiarkan utuh.
            if ($merged !== []) {
                $merged['kelompok_id'] = $entry['kelompok_id'];
                $merged['kelas_id'] = $entry['kelas_id'] ?? null;
                $merged['dicatat_oleh'] = $request->user()->id;
                $merged['client_uuid'] = $entry['client_uuid'];

                $peserta = KegiatanPeserta::updateOrCreate(
                    ['kegiatan_id' => $entry['kegiatan_id'], 'generus_id' => $entry['generus_id']],
                    $merged
                );

                if (($merged['status_presensi'] ?? null) === 'ALPHA') {
                    PresensiAlphaDicatat::dispatch($peserta);
                }

                $synced[] = $entry['client_uuid'];
            }
        }

        return response()->json(['synced' => $synced, 'conflicts' => $conflicts]);
    }

    public function realisasiKegiatan(Request $request)
    {
        $data = $request->validate([
            'entries' => ['required', 'array'],
            'entries.*.client_uuid' => ['required', 'uuid'],
            'entries.*.kegiatan_id' => ['required', 'integer', 'exists:kegiatan,id'],
            'entries.*.realisasi_status' => ['required', 'in:SESUAI_JADWAL,TIDAK_TERLAKSANA,PENGGANTI'],
            'entries.*.realisasi_catatan' => ['nullable', 'string'],
            'entries.*.updated_at' => ['required', 'date'],
        ]);

        $synced = [];
        $conflicts = [];

        foreach ($data['entries'] as $entry) {
            $existing = Kegiatan::whereKey($entry['kegiatan_id'])->whereNotNull('kurikulum_kalender_id')->first();

            if (! $existing) {
                continue;
            }

            [$merged, $conflictedFields] = $this->resolveFieldsLastWriteWins(
                $existing,
                [
                    'realisasi_status' => $entry['realisasi_status'],
                    'realisasi_catatan' => $entry['realisasi_catatan'] ?? null,
                ],
                Carbon::parse($entry['updated_at'])
            );

            if ($conflictedFields !== []) {
                $conflicts[] = [
                    'client_uuid' => $entry['client_uuid'],
                    'resolved_field_winner' => $existing->updated_at->toIso8601String(),
                    'message' => 'Realisasi Kegiatan sudah diperbarui oleh pengguna lain pada kolom '
                        .implode(', ', $conflictedFields).', silakan cek kembali',
                ];
            }

            if ($merged !== []) {
                $existing->update($merged);
                $synced[] = $entry['client_uuid'];
            }
        }

        return response()->json(['synced' => $synced, 'conflicts' => $conflicts]);
    }

    /**
     * Bandingkan tiap kolom di $incomingValues terhadap timestamp per-kolom yang
     * tersimpan di $existing->field_updated_at (last-write-wins PER FIELD, bukan per
     * baris utuh). Baris lama yang belum punya field_updated_at (mis. dibuat lewat
     * InputPresensiKegiatan secara online, bukan lewat sync) fallback ke updated_at
     * bawaan Eloquent sebagai titik pembanding.
     *
     * @return array{0: array<string, mixed>, 1: list<string>} [nilai yang menang + field_updated_at baru, daftar nama kolom yang kalah]
     */
    private function resolveFieldsLastWriteWins($existing, array $incomingValues, Carbon $incomingUpdatedAt): array
    {
        if (! $existing) {
            $incomingValues['field_updated_at'] = array_fill_keys(array_keys($incomingValues), $incomingUpdatedAt->toIso8601String());

            return [$incomingValues, []];
        }

        $fieldTimestamps = $existing->field_updated_at ?? [];
        $merged = [];
        $conflicted = [];

        foreach ($incomingValues as $field => $value) {
            $fieldLastUpdate = isset($fieldTimestamps[$field])
                ? Carbon::parse($fieldTimestamps[$field])
                : $existing->updated_at;

            if ($fieldLastUpdate && $fieldLastUpdate->gt($incomingUpdatedAt)) {
                $conflicted[] = $field;

                continue;
            }

            $merged[$field] = $value;
            $fieldTimestamps[$field] = $incomingUpdatedAt->toIso8601String();
        }

        if ($merged !== []) {
            $merged['field_updated_at'] = $fieldTimestamps;
        }

        return [$merged, $conflicted];
    }
}
