<?php

namespace App\Models;

use App\Models\Concerns\HasPolymorphicPenyelenggara;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Multi-tingkat sejak ekspansi model-role (UC-15 diperluas, Struktur-Organisasi-dan-Role.md
 * §Matriks) — pola `tingkat`/`penyelenggara_type`/`penyelenggara_id` sama seperti Kegiatan
 * (SRS-Fase-1 §17.8), jadi TIDAK memakai global scope `BelongsToKelompok` (yang cuma
 * mengenal kelompok_id tunggal) — scoping dilakukan manual di Livewire component.
 */
class Musyawaroh extends Model
{
    use HasPolymorphicPenyelenggara;

    public $timestamps = false;

    protected $table = 'musyawaroh';

    protected $fillable = [
        'kelompok_id', 'tingkat', 'penyelenggara_type', 'penyelenggara_id',
        'jenis_musyawaroh_id', 'tanggal', 'jumlah_hadir', 'disahkan_oleh', 'disahkan_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'created_at' => 'datetime',
            'disahkan_pada' => 'datetime',
        ];
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(Kelompok::class);
    }

    public function disahkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disahkan_oleh');
    }

    public function jenisMusyawaroh(): BelongsTo
    {
        return $this->belongsTo(JenisMusyawaroh::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MusyawarohItem::class);
    }
}
