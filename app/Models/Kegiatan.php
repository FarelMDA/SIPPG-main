<?php

namespace App\Models;

use App\Models\Concerns\HasKelompokCakupan;
use App\Models\Concerns\HasPolymorphicPenyelenggara;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kegiatan extends Model
{
    use HasKelompokCakupan, HasPolymorphicPenyelenggara;

    protected $table = 'kegiatan';

    protected $fillable = [
        'nama', 'deskripsi', 'tingkat', 'penyelenggara_type', 'penyelenggara_id',
        'jenis_kegiatan_id', 'tanggal', 'sesi_ke', 'tempat', 'target_tipe', 'kegiatan_jadwal_id',
        'kegiatan_program_id', 'status', 'catatan_status', 'dibuat_oleh',
        'kurikulum_kalender_id', 'materi', 'realisasi_status', 'realisasi_catatan', 'field_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'materi' => 'array',
            'field_updated_at' => 'array',
        ];
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function jenisKegiatan(): BelongsTo
    {
        return $this->belongsTo(JenisKegiatan::class);
    }

    /** Sumber breakdown Kurikulum kejadian ini — null untuk Kegiatan non-KBM (docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5). */
    public function kurikulumKalender(): BelongsTo
    {
        return $this->belongsTo(KurikulumKalender::class);
    }

    public function peserta(): HasMany
    {
        return $this->hasMany(KegiatanPeserta::class);
    }

    public function petugasPresensi(): HasMany
    {
        return $this->hasMany(KegiatanPetugasPresensi::class);
    }

    /** Jadwal Kegiatan Berulang asal kejadian ini — null untuk Kegiatan Insidental (SRS-Fase-2 §2.1). */
    public function kegiatanJadwal(): BelongsTo
    {
        return $this->belongsTo(KegiatanJadwal::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(KegiatanProgram::class, 'kegiatan_program_id');
    }

    /** Penargetan peserta (SRS-Fase-2 §2.4) — kosong bila target_tipe=SEMUA. */
    public function targetKelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kegiatan_target_kelas');
    }

    public function targetIndividu(): BelongsToMany
    {
        return $this->belongsToMany(Generus::class, 'kegiatan_target_individu', 'kegiatan_id', 'generus_id');
    }
}
