<?php

namespace App\Models;

use App\Models\Concerns\HasKelompokCakupan;
use App\Models\Concerns\HasPolymorphicPenyelenggara;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pola Jadwal Kegiatan Berulang (SRS-Fase-2 §2.2, UC-28) — menghasilkan banyak baris
 * `Kegiatan` sekaligus lewat App\Services\Kegiatan\GeneratorKegiatanDariJadwal (UC-30).
 */
class KegiatanJadwal extends Model
{
    use HasFactory, HasKelompokCakupan, HasPolymorphicPenyelenggara;

    protected $table = 'kegiatan_jadwal';

    protected $fillable = [
        'nama', 'deskripsi', 'tingkat', 'penyelenggara_type', 'penyelenggara_id',
        'jenis_kegiatan_id', 'frekuensi_tipe', 'hari_dalam_minggu', 'minggu_ke_dalam_bulan',
        'interval_minggu', 'jumlah_sesi_per_kemunculan', 'tanggal_mulai', 'tanggal_selesai',
        'tempat', 'rotasi_tempat', 'target_tipe', 'kegiatan_program_id', 'status', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'hari_dalam_minggu' => 'array',
            'minggu_ke_dalam_bulan' => 'array',
            'rotasi_tempat' => 'array',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function kegiatan(): HasMany
    {
        return $this->hasMany(Kegiatan::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(KegiatanProgram::class, 'kegiatan_program_id');
    }

    public function jenisKegiatan(): BelongsTo
    {
        return $this->belongsTo(JenisKegiatan::class);
    }

    /** Penargetan peserta (SRS-Fase-2 §2.4) — disalin/snapshot ke Kegiatan::targetKelas() saat generate. */
    public function targetKelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kegiatan_jadwal_target_kelas');
    }

    public function targetIndividu(): BelongsToMany
    {
        return $this->belongsToMany(Generus::class, 'kegiatan_jadwal_target_individu', 'kegiatan_jadwal_id', 'generus_id');
    }
}
