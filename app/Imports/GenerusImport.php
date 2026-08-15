<?php

namespace App\Imports;

use App\Events\GenerusDisimpan;
use App\Models\Generus;
use App\Models\JenisKelamin;
use App\Models\Jenjang;
use App\Models\Kelas;
use App\Models\StatusDomisili;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * UC-08 — Impor Massal Generus. UCIC UC-08.
 *
 * Header kolom .xlsx yang diharapkan (baris pertama):
 * nama | tanggal_lahir | jenis_kelamin | kelas | nama_orang_tua | nomor_hp_orang_tua | status_domisili
 */
class GenerusImport implements ToCollection, WithHeadingRow
{
    public int $sukses = 0;

    /** @var array<int, array{baris: int, alasan: string}> */
    public array $gagal = [];

    public function collection(BaseCollection $rows): void
    {
        foreach ($rows as $index => $row) {
            $baris = $index + 2; // +1 heading row, +1 index dimulai dari 0

            $namaJenjang = trim((string) ($row['kelas'] ?? ''));
            $jenjang = Jenjang::where('label', $namaJenjang)->first();
            $kelas = $jenjang ? Kelas::where('jenjang_id', $jenjang->id)->first() : null;

            $data = [
                'nama' => trim((string) ($row['nama'] ?? '')),
                'tanggal_lahir' => (string) ($row['tanggal_lahir'] ?? ''),
                'jenis_kelamin' => strtoupper(trim((string) ($row['jenis_kelamin'] ?? ''))),
                'kelas_id' => $kelas?->id,
                'jenjang_id' => $jenjang?->id,
                'nama_orang_tua' => trim((string) ($row['nama_orang_tua'] ?? '')),
                'nomor_hp_orang_tua' => trim((string) ($row['nomor_hp_orang_tua'] ?? '')),
                'status_domisili' => strtoupper(trim((string) ($row['status_domisili'] ?? 'SETEMPAT'))),
            ];

            $validator = Validator::make($data, [
                'nama' => ['required', 'string', 'max:255'],
                'tanggal_lahir' => ['required', 'date'],
                'jenis_kelamin' => ['required', Rule::in(JenisKelamin::pluck('kode'))],
                'kelas_id' => ['required', 'exists:kelas,id'],
                'jenjang_id' => ['required', 'exists:jenjang,id'],
                'nama_orang_tua' => ['required', 'string', 'max:255'],
                'nomor_hp_orang_tua' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{8,11}$/'],
                'status_domisili' => ['required', Rule::in(StatusDomisili::pluck('kode'))],
            ]);

            if ($validator->fails()) {
                $this->gagal[] = ['baris' => $baris, 'alasan' => implode(' ', $validator->errors()->all())];

                continue;
            }

            $generus = Generus::create($data);
            GenerusDisimpan::dispatch($generus);

            $this->sukses++;
        }

        activity('generus')
            ->causedBy(Auth::guard('web')->user())
            ->withProperties(['sukses' => $this->sukses, 'gagal' => count($this->gagal)])
            ->log('Impor massal Generus');
    }
}
