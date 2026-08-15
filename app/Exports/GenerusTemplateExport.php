<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GenerusTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['nama', 'tanggal_lahir', 'jenis_kelamin', 'kelas', 'nama_orang_tua', 'nomor_hp_orang_tua', 'status_domisili'];
    }

    public function array(): array
    {
        return [
            ['Fulan bin Fulan', '2015-01-15', 'LAKI', 'Dasar 1', 'Fulan Ayah', '081234567890', 'SETEMPAT'],
        ];
    }
}
