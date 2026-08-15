<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laporan Bulanan otomatis (SRS-Fase-2 §3, UCIC-Fase-2 UC-31/UC-32) — Kelompok
 * (`kelompok_id` terisi), agregasi Desa (`desa_id` terisi), atau agregasi Daerah
 * (`daerah_id` terisi, SRS §3.5). `snapshot_data` NULL selama DRAFT (dihitung live lewat
 * App\Services\Laporan\SusunSnapshotLaporan) KECUALI untuk laporan tingkat DESA/DAERAH,
 * yang sudah terisi sejak generate() karena isinya murni gabungan snapshot jenjang di
 * bawahnya yang sudah beku (App\Services\Laporan\AgregasiLaporanDesa/AgregasiLaporanDaerah)
 * — tidak ada lagi yang "live" untuk dihitung ulang di level Desa/Daerah (SRS §3.4/§3.5).
 * Laporan tingkat DAERAH tidak pernah mencapai DISETUJUI/REVISI_DIMINTA — Daerah adalah
 * jenjang teratas struktur organisasi (PRD §18.4), FINAL sudah jadi status akhir (SRS §3.1).
 */
class LaporanBulanan extends Model
{
    use HasFactory;

    protected $table = 'laporan_bulanan';

    protected $fillable = [
        'kelompok_id', 'desa_id', 'daerah_id', 'tingkat', 'periode', 'versi', 'status',
        'snapshot_data', 'catatan_revisi', 'difinalisasi_oleh', 'difinalisasi_pada',
        'disetujui_oleh', 'disetujui_pada', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'versi' => 'integer',
            'snapshot_data' => 'array',
            'difinalisasi_pada' => 'datetime',
            'disetujui_pada' => 'datetime',
        ];
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    public function daerah(): BelongsTo
    {
        return $this->belongsTo(Daerah::class);
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function difinalisasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'difinalisasi_oleh');
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    /** DRAFT tingkat KELOMPOK dihitung live; DRAFT tingkat DESA sudah beku sejak generate (lihat docblock kelas). */
    public function isLive(): bool
    {
        return $this->status === 'DRAFT' && $this->tingkat === 'KELOMPOK';
    }
}
