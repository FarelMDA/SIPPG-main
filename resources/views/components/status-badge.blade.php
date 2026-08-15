@props(['status'])
@php
    // Mapping status → variant sesuai UIUX-Reference §7.2 / §19 SIPPP Design System.
    $map = [
        'HADIR' => ['success', 'Hadir'],
        'IZIN' => ['warning', 'Izin'],
        'SAKIT' => ['warning', 'Sakit'],
        'ALPHA' => ['danger', 'Alpha'],
        'SETEMPAT' => ['info', 'Setempat'],
        'PENDATANG' => ['neutral', 'Pendatang'],
        'KELOMPOK' => ['info', 'Kelompok'],
        'DESA' => ['warning', 'Desa'],
        'DAERAH' => ['success', 'Daerah'],
        'SESUAI_JADWAL' => ['success', 'Sesuai Jadwal'],
        'PENGGANTI' => ['info', 'Pengganti'],
        'TERJADWAL' => ['neutral', 'Terjadwal'],
        'TERLAKSANA' => ['success', 'Terlaksana'],
        'TIDAK_TERLAKSANA' => ['danger', 'Tidak Terlaksana'],
        'BELUM' => ['neutral', 'Belum'],
        'BELUM_MULAI' => ['neutral', 'Belum Mulai'],
        'PROSES' => ['warning', 'Proses'],
        'BERJALAN' => ['warning', 'Berjalan'],
        'SELESAI' => ['success', 'Selesai'],
        'AKTIF' => ['success', 'Aktif'],
        'TIDAK_AKTIF' => ['neutral', 'Tidak Aktif'],
        // Laporan Bulanan (SRS-Fase-2 §3, UIUX-Reference-Fase-2 §3.3.2)
        'DRAFT' => ['neutral', 'Draft'],
        'FINAL' => ['info', 'Final'],
        'DISETUJUI' => ['success', 'Disetujui'],
        'REVISI_DIMINTA' => ['danger', 'Revisi Diminta'],
    ];
    [$variant, $label] = $map[$status] ?? ['neutral', $status];
@endphp
<x-badge :variant="$variant">{{ $label }}</x-badge>
