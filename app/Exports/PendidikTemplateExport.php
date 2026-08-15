<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PendidikTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['nama', 'jenis', 'kelompok', 'kelas'];
    }

    public function array(): array
    {
        return [
            ['Ust. Fulan', 'MT', 'KBM Melati', 'Dasar 1, Dasar 2'],
        ];
    }
}
