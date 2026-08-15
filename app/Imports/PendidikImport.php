<?php

namespace App\Imports;

use App\Models\JenisPendidik;
use App\Models\Kelas;
use App\Models\Pendidik;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * UC-08 — Impor Massal Pendidik. UCIC UC-08.
 *
 * Header kolom .xlsx: nama | jenis | kelompok | kelas (dipisah koma, opsional)
 */
class PendidikImport implements ToCollection, WithHeadingRow
{
    public int $sukses = 0;

    /** @var array<int, array{baris: int, alasan: string}> */
    public array $gagal = [];

    public function collection(BaseCollection $rows): void
    {
        foreach ($rows as $index => $row) {
            $baris = $index + 2;

            $kelompok = \App\Models\Kelompok::where('nama', trim((string) ($row['kelompok'] ?? '')))->first();

            $data = [
                'nama' => trim((string) ($row['nama'] ?? '')),
                'jenis' => strtoupper(trim((string) ($row['jenis'] ?? ''))),
                'kelompok_id' => $kelompok?->id,
            ];

            $validator = Validator::make($data, [
                'nama' => ['required', 'string', 'max:255'],
                'jenis' => ['required', Rule::in(JenisPendidik::pluck('kode'))],
                'kelompok_id' => ['required', 'exists:kelompok,id'],
            ]);

            if ($validator->fails()) {
                $this->gagal[] = ['baris' => $baris, 'alasan' => implode(' ', $validator->errors()->all())];

                continue;
            }

            $pendidik = Pendidik::create($data);

            $namaKelas = array_filter(array_map('trim', explode(',', (string) ($row['kelas'] ?? ''))));
            if ($namaKelas) {
                $kelasIds = Kelas::where('kelompok_id', $data['kelompok_id'])
                    ->whereHas('jenjang', fn ($q) => $q->whereIn('label', $namaKelas))
                    ->pluck('id');
                $pendidik->kelas()->sync($kelasIds);
            }

            $this->sukses++;
        }

        activity('pendidik')
            ->causedBy(Auth::guard('web')->user())
            ->withProperties(['sukses' => $this->sukses, 'gagal' => count($this->gagal)])
            ->log('Impor massal Pendidik');
    }
}
